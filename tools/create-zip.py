#!/usr/bin/env python3

from __future__ import annotations

import datetime
import pathlib
import sys
import zipfile


def main() -> int:
    if len(sys.argv) != 4:
        raise SystemExit("Usage: create-zip.py <package-root> <archive> <source-date-epoch>")

    package = pathlib.Path(sys.argv[1])
    archive = pathlib.Path(sys.argv[2])
    timestamp = datetime.datetime.fromtimestamp(int(sys.argv[3]), datetime.timezone.utc)
    zip_timestamp = (
        max(timestamp.year, 1980),
        timestamp.month,
        timestamp.day,
        timestamp.hour,
        timestamp.minute,
        timestamp.second,
    )

    files = sorted(path for path in package.rglob("*") if path.is_file())
    with zipfile.ZipFile(
        archive,
        mode="w",
        compression=zipfile.ZIP_DEFLATED,
        compresslevel=9,
    ) as output:
        for path in files:
            name = path.as_posix()
            info = zipfile.ZipInfo(name, zip_timestamp)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            output.writestr(info, path.read_bytes(), compresslevel=9)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
