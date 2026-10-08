#!/usr/bin/env python3
"""Offline regression tests for install and HTTP target provenance guards."""

from contextlib import contextmanager
import hashlib
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
import socket
import subprocess
import sys
import tempfile
import threading
import unittest
import zipfile

INFRA = Path(__file__).resolve().parent


def command(script, *args):
    return subprocess.run([sys.executable, str(INFRA / script), *map(str, args)], capture_output=True, text=True)


class PackageProvenanceTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.package = self.root / "component.zip"
        self.entries = {
            "sample.xml": '''<extension type="component"><files folder="site"><folder>src</folder></files>
                <administration><files folder="admin"><folder>src</folder><folder>tmpl</folder><folder>compiler</folder></files></administration>
                <api><files folder="api"><folder>src</folder></files></api>
                <media destination="com_sample" folder="media"><folder>js</folder></media></extension>''',
            "admin/src/Controller.php": "admin controller",
            "admin/tmpl/default.php": "template",
            "admin/compiler/joomla_4/API.php": "compiler template",
            "site/src/View.php": "site view",
            "api/src/Controller.php": "API controller",
            "media/js/sample.js": "browser script",
            "libraries/vendor_jcb/VDM.Joomla/src/Example.php": "library",
            "libraries/vendor_jcb/autoload.php": "autoload",
            "not-installed.txt": "must not count as installed",
        }

    def build(self):
        with zipfile.ZipFile(self.package, "w") as archive:
            for name, body in self.entries.items():
                archive.writestr(name, body)
        return command("package-manifest.py", self.package)

    def installed(self):
        result = self.build()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.checksums = self.root / "installed.sha256"
        self.checksums.write_text(result.stdout)
        self.site = self.root / "site"
        self.site.mkdir()
        mapping = {
            "admin/": "administrator/components/com_sample/",
            "site/": "components/com_sample/",
            "api/": "api/components/com_sample/",
            "media/": "media/com_sample/",
            "libraries/": "libraries/",
        }
        for source, body in self.entries.items():
            for prefix, destination in mapping.items():
                if source.startswith(prefix):
                    path = self.site / (destination + source[len(prefix):])
                    path.parent.mkdir(parents=True, exist_ok=True)
                    path.write_text(body)
        return result.stdout

    def verify(self):
        return subprocess.run(["sha256sum", "--check", str(self.checksums)], cwd=self.site, capture_output=True)

    def test_all_runtime_entrypoints_and_libraries_are_verified(self):
        manifest = self.installed()
        self.assertEqual(len(manifest.splitlines()), 8)
        self.assertNotIn("not-installed", manifest)
        self.assertEqual(self.verify().returncode, 0)

    def test_changed_controller_template_api_or_library_fails(self):
        self.installed()
        for relative in ("administrator/components/com_sample/src/Controller.php",
                         "administrator/components/com_sample/tmpl/default.php",
                         "administrator/components/com_sample/compiler/joomla_4/API.php",
                         "api/components/com_sample/src/Controller.php",
                         "libraries/vendor_jcb/VDM.Joomla/src/Example.php"):
            with self.subTest(relative=relative):
                path = self.site / relative
                original = path.read_bytes()
                path.write_bytes(b"released version instead of this package")
                self.assertNotEqual(self.verify().returncode, 0)
                path.write_bytes(original)

    def test_missing_installed_file_fails(self):
        self.installed()
        (self.site / "libraries/vendor_jcb/autoload.php").unlink()
        self.assertNotEqual(self.verify().returncode, 0)

    def test_installer_can_consume_archive_after_manifest_is_captured(self):
        self.installed()
        self.package.unlink()
        self.assertEqual(self.verify().returncode, 0)
        (self.site / "api/components/com_sample/src/Controller.php").write_text("different installed source")
        self.assertNotEqual(self.verify().returncode, 0)

    def test_missing_declared_package_folder_fails(self):
        del self.entries["api/src/Controller.php"]
        self.assertNotEqual(self.build().returncode, 0)

    def test_traversal_or_checksum_line_injection_fails(self):
        for path in ("../escape", "admin/../../escape", "/absolute", "path\nchecksum", "path\\escape"):
            with self.subTest(path=path):
                self.entries[path] = "bad"
                self.assertNotEqual(self.build().returncode, 0)
                del self.entries[path]

    def test_manifest_destination_traversal_fails(self):
        self.entries["sample.xml"] = self.entries["sample.xml"].replace('destination="com_sample"', 'destination="../escape"')
        self.assertNotEqual(self.build().returncode, 0)

    def test_plugin_uses_native_group_and_plugin_attribute(self):
        self.entries = {
            "demo.xml": '<extension type="plugin" group="webservices"><files><folder plugin="demo">services</folder><folder>src</folder></files></extension>',
            "services/provider.php": "provider",
            "src/Extension/Demo.php": "route registration",
        }
        result = self.build()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("plugins/webservices/demo/services/provider.php", result.stdout)
        self.assertIn("plugins/webservices/demo/src/Extension/Demo.php", result.stdout)

    def test_empty_or_ambiguous_manifest_fails(self):
        self.entries = {"one.xml": '<extension type="component"/>'}
        self.assertNotEqual(self.build().returncode, 0)
        self.entries["two.xml"] = self.entries["one.xml"]
        self.assertNotEqual(self.build().returncode, 0)


class SiteProofTest(unittest.TestCase):
    name = "jcb-site-proof-" + "a" * 32 + ".txt"
    value = "b" * 64

    @contextmanager
    def server(self, status=200, body=None, redirect=None):
        requests = []
        value = self.value if body is None else body

        class Handler(BaseHTTPRequestHandler):
            def do_GET(self):
                requests.append(self.path)
                self.send_response(status)
                if redirect:
                    self.send_header("Location", redirect)
                self.end_headers()
                self.wfile.write(value.encode())

            def log_message(self, *args):
                pass

        server = ThreadingHTTPServer(("127.0.0.1", 0), Handler)
        thread = threading.Thread(target=server.serve_forever, kwargs={"poll_interval": 0.01}, daemon=True)
        thread.start()
        try:
            yield f"http://127.0.0.1:{server.server_port}", requests
        finally:
            server.shutdown()
            server.server_close()
            thread.join()

    def test_exact_site_proof_passes_without_credentials(self):
        with self.server() as (url, requests):
            result = command("site-proof.py", "verify", url, self.name, self.value)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(requests, ["/" + self.name])
            self.assertIn(hashlib.sha256(self.value.encode()).hexdigest(), result.stdout)

    def test_wrong_site_body_fails(self):
        with self.server(body="unrelated site's login page") as (url, _):
            self.assertNotEqual(command("site-proof.py", "verify", url, self.name, self.value).returncode, 0)

    def test_http_error_with_correct_body_fails(self):
        with self.server(status=500) as (url, _):
            self.assertNotEqual(command("site-proof.py", "verify", url, self.name, self.value).returncode, 0)

    def test_redirect_is_rejected_without_following(self):
        with self.server(status=302, redirect="/another-target") as (url, requests):
            self.assertNotEqual(command("site-proof.py", "verify", url, self.name, self.value).returncode, 0)
            self.assertEqual(requests, ["/" + self.name])

    def test_custom_base_path_stays_on_the_explicit_target(self):
        with self.server() as (url, requests):
            self.assertEqual(command("site-proof.py", "verify", url + "/joomla/", self.name, self.value).returncode, 0)
            self.assertEqual(requests, ["/joomla/" + self.name])

    def test_dead_new_server_cannot_use_an_existing_http_response(self):
        child = subprocess.Popen([sys.executable, "-c", "pass"])
        child.wait()
        with self.server() as (url, requests):
            result = command("site-proof.py", "verify", url, self.name, self.value, "--pid", child.pid)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn("server exited", result.stderr)
            self.assertEqual(requests, [])

    def test_occupied_port_fails(self):
        with self.server() as (url, _):
            self.assertNotEqual(command("site-proof.py", "port", url.rsplit(":", 1)[1]).returncode, 0)

    def test_unused_port_passes_and_no_server_times_out(self):
        with socket.socket() as listener:
            listener.bind(("127.0.0.1", 0))
            port = listener.getsockname()[1]
        self.assertEqual(command("site-proof.py", "port", port).returncode, 0)
        result = command("site-proof.py", "verify", f"http://127.0.0.1:{port}", self.name, self.value, "--timeout", 0.1)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Timed out", result.stderr)

    def test_invalid_target_credentials_and_proof_paths_fail_before_request(self):
        with self.server() as (url, requests):
            for target, name in ((url.replace("http://", "http://user:password@"), self.name),
                                 (url + "?query=1", self.name), (url, "../escape")):
                self.assertNotEqual(command("site-proof.py", "verify", target, name, self.value).returncode, 0)
            self.assertEqual(requests, [])

    def test_fresh_markers_have_exact_bytes_and_different_names(self):
        with tempfile.TemporaryDirectory() as directory:
            first = command("site-proof.py", "create", directory)
            second = command("site-proof.py", "create", directory)
            self.assertEqual(first.returncode, 0, first.stderr)
            self.assertEqual(second.returncode, 0, second.stderr)
            first_name, first_value = first.stdout.split()
            second_name, second_value = second.stdout.split()
            self.assertNotEqual(first_name, second_name)
            self.assertNotEqual(first_value, second_value)
            self.assertEqual((Path(directory) / first_name).read_text(), first_value)


if __name__ == "__main__":
    unittest.main(verbosity=2)
