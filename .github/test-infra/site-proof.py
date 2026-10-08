#!/usr/bin/env python3
"""Prove an HTTP test target serves a fresh file from the installed test site.

No credentials or redirects are used. Only connection startup is retried;
an HTTP error, redirect, or different body is a provenance failure.
"""

import argparse
import hashlib
import os
from pathlib import Path
import re
import secrets
import socket
import sys
import time
import urllib.error
import urllib.parse
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def process_alive(pid):
    try:
        os.kill(pid, 0)
        status = Path(f"/proc/{pid}/stat")
        if status.exists() and status.read_text().rpartition(") ")[2].startswith("Z "):
            return False
        return True
    except ProcessLookupError:
        return False


def verify(base_url, name, value, timeout, pid=None):
    parsed = urllib.parse.urlsplit(base_url)
    if (parsed.scheme not in ("http", "https") or not parsed.hostname
            or parsed.username or parsed.password or parsed.query or parsed.fragment):
        raise ValueError("Site URL must be HTTP(S), without credentials, query, or fragment")
    if not re.fullmatch(r"jcb-site-proof-[0-9a-f]{32}\.txt", name):
        raise ValueError("Invalid site proof filename")
    if not re.fullmatch(r"[0-9a-f]{64}", value):
        raise ValueError("Invalid site proof value")
    if timeout < 0 or timeout > 120:
        raise ValueError("Readiness timeout must be between 0 and 120 seconds")
    opener = urllib.request.build_opener(NoRedirect())
    url = base_url.rstrip("/") + "/" + name
    deadline = time.monotonic() + timeout
    while True:
        if pid is not None and not process_alive(pid):
            raise ValueError("The newly started HTTP server exited before readiness")
        try:
            request = urllib.request.Request(url, headers={"Cache-Control": "no-cache"})
            with opener.open(request, timeout=max(0.1, min(3, deadline - time.monotonic()))) as response:
                if response.status != 200 or response.read(1024) != value.encode("ascii"):
                    raise ValueError("HTTP target does not serve the installed site's exact proof")
            if pid is not None and not process_alive(pid):
                raise ValueError("The newly started HTTP server exited during readiness")
            print("Verified HTTP 200 and installed-site proof SHA256 " + hashlib.sha256(value.encode()).hexdigest())
            return
        except urllib.error.HTTPError as error:
            raise ValueError(f"Site proof returned HTTP {error.code}; redirects are not followed") from error
        except (urllib.error.URLError, TimeoutError, ConnectionError) as error:
            if time.monotonic() >= deadline:
                raise ValueError("Timed out waiting for the installed site's HTTP proof") from error
            time.sleep(min(0.2, max(0, deadline - time.monotonic())))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest="command", required=True)
    create = sub.add_parser("create")
    create.add_argument("directory")
    port = sub.add_parser("port")
    port.add_argument("number", type=int)
    check = sub.add_parser("verify")
    check.add_argument("base_url")
    check.add_argument("name")
    check.add_argument("value")
    check.add_argument("--timeout", type=float, default=30)
    check.add_argument("--pid", type=int)
    args = parser.parse_args()
    if args.command == "create":
        name, value = "jcb-site-proof-" + secrets.token_hex(16) + ".txt", secrets.token_hex(32)
        with (Path(args.directory) / name).open("x", encoding="ascii") as stream:
            stream.write(value)
        print(name, value)
    elif args.command == "port":
        if not 1 <= args.number <= 65535:
            raise ValueError("HTTP port must be between 1 and 65535")
        with socket.socket() as listener:
            # A stopped test server can leave TIME_WAIT sockets behind; an
            # active listener still prevents this bind (no SO_REUSEPORT).
            listener.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
            listener.bind(("127.0.0.1", args.number))
        print("Test HTTP port is available")
    else:
        verify(args.base_url, args.name, args.value, args.timeout, args.pid)


if __name__ == "__main__":
    try:
        main()
    except (ValueError, OSError) as error:
        print(f"Site provenance failed: {error}", file=sys.stderr)
        sys.exit(1)
