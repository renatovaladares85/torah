#!/usr/bin/env bash

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"

ref="${1:-HEAD}"
commit="$(git rev-parse --verify "${ref}^{commit}" 2>/dev/null)" || {
    printf 'Unknown Git ref: %s\n' "$ref" >&2
    exit 1
}

tools/check-public-repo.sh

setup="$(git show "${commit}:setup.php")"
plugin_xml="$(git show "${commit}:plugin.xml")"
version="$(sed -n "s/^define('PLUGIN_TORAH_VERSION', '\([^']*\)');$/\1/p" <<< "$setup")"
xml_version="$(sed -n 's/.*<num>\([^<]*\)<\/num>.*/\1/p' <<< "$plugin_xml" | head -n 1)"

if [[ -z "$version" || "$version" != "$xml_version" ]]; then
    printf 'Version mismatch at %s: setup.php=%s plugin.xml=%s\n' "$ref" "$version" "$xml_version" >&2
    exit 1
fi

manifest="$root/tools/package-files.txt"
mapfile -t allowlist < <(sed '/^[[:space:]]*#/d; /^[[:space:]]*$/d' "$manifest")
if [[ "${#allowlist[@]}" -eq 0 ]]; then
    printf 'Production package manifest is empty.\n' >&2
    exit 1
fi

mkdir -p var dist
stage="$(mktemp -d "$root/var/package.XXXXXX")"
trap 'rm -rf "$stage"' EXIT
mkdir -p "$stage/torah"

git archive --format=tar "$commit" -- "${allowlist[@]}" \
    | tar --extract --file=- --directory="$stage/torah"

source_date_epoch="$(git show --no-patch --format=%ct "$commit")"
find "$stage" -exec touch --date="@${source_date_epoch}" {} +

tar_archive="$root/dist/torah-${version}.tar.gz"
zip_archive="$root/dist/torah-${version}.zip"
checksums="$root/dist/torah-${version}.sha256"
zip_temporary="$root/dist/.torah-${version}.zip.tmp.zip"

tar --sort=name \
    --owner=0 --group=0 --numeric-owner \
    --mode='a-x,a+r,u+w,go-w,a+X' \
    --mtime="@${source_date_epoch}" \
    --format=gnu \
    --directory="$stage" \
    --create --file=- torah \
    | gzip -n -9 > "${tar_archive}.tmp"
mv "${tar_archive}.tmp" "$tar_archive"

(
    cd "$stage"
    rm -f "$zip_temporary"
    python3 "$root/tools/create-zip.py" torah "$zip_temporary" "$source_date_epoch"
)
mv "$zip_temporary" "$zip_archive"

tools/validate-package.sh "$tar_archive"
tools/validate-package.sh "$zip_archive"

(
    cd dist
    sha256sum "$(basename "$tar_archive")" "$(basename "$zip_archive")" > "$(basename "$checksums")"
)

printf 'Created production packages from %s (%s):\n' "$ref" "$commit"
printf '  %s\n  %s\n  %s\n' "$tar_archive" "$zip_archive" "$checksums"
printf 'Package contents:\n'
tar --list --gzip --file="$tar_archive"
