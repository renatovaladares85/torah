#!/usr/bin/env bash

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$root"

version="$(git show HEAD:setup.php | sed -n "s/^define('PLUGIN_TORAH_VERSION', '\([^']*\)');$/\1/p")"
tar_archive="dist/torah-${version}.tar.gz"
zip_archive="dist/torah-${version}.zip"
checksums="dist/SHA256SUMS.txt"

tools/package.sh HEAD >/dev/null
first_tar="$(sha256sum "$tar_archive" | cut -d ' ' -f 1)"
first_zip="$(sha256sum "$zip_archive" | cut -d ' ' -f 1)"

tools/package.sh HEAD >/dev/null
second_tar="$(sha256sum "$tar_archive" | cut -d ' ' -f 1)"
second_zip="$(sha256sum "$zip_archive" | cut -d ' ' -f 1)"

if [[ "$first_tar" != "$second_tar" || "$first_zip" != "$second_zip" ]]; then
    printf 'Production packages are not reproducible.\n' >&2
    exit 1
fi

tar --list --gzip --file="$tar_archive" | sed '/\/$/d' | LC_ALL=C sort > var/package-tar-files.txt
unzip -Z1 "$zip_archive" | sed '/\/$/d' | LC_ALL=C sort > var/package-zip-files.txt
if ! diff --unified var/package-tar-files.txt var/package-zip-files.txt; then
    printf 'Tar and ZIP packages do not contain the same files.\n' >&2
    exit 1
fi
rm -f var/package-tar-files.txt var/package-zip-files.txt

tools/validate-package.sh "$tar_archive"
tools/validate-package.sh "$zip_archive"
(
    cd dist
    sha256sum --check "$(basename "$checksums")"
)

printf 'Production package regression test passed.\n'
