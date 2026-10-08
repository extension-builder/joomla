#!/usr/bin/env python3
"""Map a JCB component/plugin ZIP to the files Joomla must have installed.

Like the golden harness, emit SHA256 checks against the actual archive, not a
second checkout. Native manifest files/media mappings cover the runtime entry
points; JCB's installer also copies libraries/vendor_jcb to the site root.
Languages and installer-only files are outside this runtime provenance check.
"""

import argparse
import hashlib
from pathlib import PurePosixPath
import re
import sys
import xml.etree.ElementTree as ET
import zipfile


def safe_path(value):
    value = value.removeprefix("./").rstrip("/")
    if (not value or value.startswith("/") or "\\" in value
            or any(ord(char) < 32 for char in value)
            or any(part in ("", ".", "..") for part in value.split("/"))):
        raise ValueError(f"Unsafe package path: {value!r}")
    return value


def package_manifest(package):
    with zipfile.ZipFile(package) as archive:
        files = {}
        for entry in archive.infolist():
            if entry.is_dir():
                continue
            name = safe_path(entry.filename)
            if name in files or (entry.external_attr >> 16) & 0o170000 == 0o120000:
                raise ValueError(f"Duplicate or symlink package entry: {name}")
            files[name] = entry

        manifests = []
        for name in files:
            if "/" not in name and name.endswith(".xml"):
                document = ET.fromstring(archive.read(files[name]))
                if document.tag == "extension" and document.get("type") in ("component", "plugin"):
                    manifests.append((name, document))
        if len(manifests) != 1:
            raise ValueError("Expected exactly one root component or plugin manifest")
        manifest_name, manifest = manifests[0]
        mapped = {}

        def add(source, destination):
            source, destination = safe_path(source), safe_path(destination)
            if source not in files:
                raise ValueError(f"Manifest entry is missing from package: {source}")
            digest = hashlib.sha256(archive.read(files[source])).hexdigest()
            if destination in mapped and mapped[destination] != digest:
                raise ValueError(f"Conflicting installed destination: {destination}")
            mapped[destination] = digest

        def map_group(group, destination):
            if group is None:
                return
            source_root = group.get("folder", "").strip("/")
            if source_root:
                source_root = safe_path(source_root) + "/"
            for child in group:
                if child.tag not in ("filename", "folder"):
                    raise ValueError(f"Unsupported files element: {child.tag}")
                relative = safe_path((child.text or "").strip())
                source = source_root + relative
                if child.tag == "filename":
                    add(source, destination + "/" + relative)
                else:
                    children = [name for name in files if name.startswith(source + "/")]
                    if not children:
                        raise ValueError(f"Manifest folder has no packaged files: {source}")
                    for name in children:
                        add(name, destination + "/" + name[len(source_root):])

        if manifest.get("type") == "component":
            element = "com_" + PurePosixPath(manifest_name).stem.removeprefix("com_")
            if not re.fullmatch(r"com_[a-zA-Z0-9_]+", element):
                raise ValueError("Unsupported component element")
            map_group(manifest.find("files"), "components/" + element)
            map_group(manifest.find("administration/files"), "administrator/components/" + element)
            map_group(manifest.find("api/files"), "api/components/" + element)
            for name in files:
                if name.startswith("libraries/vendor_jcb/"):
                    add(name, name)
        else:
            group = manifest.get("group", "")
            elements = {child.get("plugin") for child in manifest.findall("files/*") if child.get("plugin")}
            if len(elements) != 1 or not re.fullmatch(r"[a-zA-Z0-9_-]+", group):
                raise ValueError("Plugin needs one element and a valid group")
            element = elements.pop()
            if not re.fullmatch(r"[a-zA-Z0-9_-]+", element):
                raise ValueError("Invalid plugin element")
            map_group(manifest.find("files"), "plugins/" + group + "/" + element)

        for media in manifest.findall("media"):
            destination = media.get("destination", "")
            map_group(media, "media" + ("/" + safe_path(destination) if destination else ""))
        if not mapped:
            raise ValueError("Package has no runtime files to verify")
        return mapped


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("package")
    args = parser.parse_args()
    try:
        for destination, digest in sorted(package_manifest(args.package).items()):
            print(f"{digest}  {destination}")
    except (ValueError, OSError, ET.ParseError, zipfile.BadZipFile) as error:
        print(f"Package provenance failed: {error}", file=sys.stderr)
        sys.exit(1)
