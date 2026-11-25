"""Exercise the component, view, and operation permissions of a compiled local Demo."""

import json
import os
import re
import sys
import urllib.error
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def main(base, release, admin, reader, view_denied, component_denied):
    if os.environ.get("JCB_DISPOSABLE_TEST") != "1" or not re.fullmatch(
        r"http://127\.0\.0\.1:\d+", base
    ):
        raise ValueError("API access tests require a disposable loopback CMS")
    if release not in {"5.4.11", "6.2.1", "6.1.2"}:
        raise ValueError(f"Unknown dispatcher contract for {release}")
    patched = release != "6.1.2"
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect())

    def request(path, token, expected, method="GET", data=None):
        headers = {"Accept": "application/vnd.api+json"}
        if token:
            headers["X-Joomla-Token"] = token
        payload = None
        if data is not None:
            headers["Content-Type"] = "application/json"
            payload = json.dumps(data).encode()
        req = urllib.request.Request(
            base + "/api/index.php/v1/demo/" + path,
            method=method, headers=headers, data=payload,
        )
        try:
            response = opener.open(req, timeout=30)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            raw = response.read()
            if response.status not in expected:
                raise AssertionError(f"{method} {path}: expected {expected}, got {response.status}: {raw[:400]!r}")
            body = json.loads(raw) if raw else {}
            if response.status >= 400 and not body.get("errors"):
                raise AssertionError(f"{method} {path}: expected JSON:API errors")
            if response.status in (200, 201) and "data" not in body:
                raise AssertionError(f"{method} {path}: expected JSON:API data")
            print(f"{method} {path}: HTTP {response.status}", flush=True)
            return body

    name = "API permission fixture"
    created = request("looks", admin, (200, 201), "POST", {"name": name, "access": 1})
    record_id = int(created["data"]["id"])
    item = f"looks/{record_id}"
    try:
        request("site_looks", None, (200,))
        request(item, None, (401,))
        request(item, None, (401,), "PATCH", {"name": "guest"})
        request(item, reader, (200,))
        request(item, reader, (403,), "PATCH", {"name": "reader"})
        assert request(item, admin, (200,))["data"]["attributes"]["name"] == name
        for path in ("qareport", f"qadetail/{record_id}"):
            # Same authenticated requests, with opposite explicit manage/API grants.
            request(path, reader, (200,) if patched else (403,))
            request(path, component_denied, (403,) if patched else (200,))
            request(path, view_denied, (403,))
    finally:
        request(item, admin, (200,), "PATCH", {"published": -2})
        request(item, admin, (204,), "DELETE")


if __name__ == "__main__":
    main(*sys.argv[1:])
