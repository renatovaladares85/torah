#!/usr/bin/env bash

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"

usage() {
    printf 'Usage: %s [--tag v<version>] [git-ref]\n' "${0##*/}" >&2
}

tag=""
ref="HEAD"
ref_supplied=0
while [[ "$#" -gt 0 ]]; do
    case "$1" in
        --tag)
            shift
            if [[ "$#" -eq 0 || -n "$tag" ]]; then
                usage
                exit 1
            fi
            tag="$1"
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            if [[ "$ref_supplied" -ne 0 ]]; then
                usage
                exit 1
            fi
            ref="$1"
            ref_supplied=1
            ;;
    esac
    shift
done

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

semver_pattern='^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?(\+[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?$'
if [[ ! "$version" =~ $semver_pattern ]]; then
    printf 'Invalid plugin version: %s\n' "$version" >&2
    exit 1
fi

if [[ -n "$tag" ]]; then
    expected_tag="v${version}"
    if [[ "$tag" != "$expected_tag" ]]; then
        printf 'Tag mismatch: expected %s, received %s\n' "$expected_tag" "$tag" >&2
        exit 1
    fi

    tag_commit="$(git rev-parse --verify "refs/tags/${tag}^{commit}" 2>/dev/null)" || {
        printf 'Unknown Git tag: %s\n' "$tag" >&2
        exit 1
    }
    if [[ "$tag_commit" != "$commit" ]]; then
        printf 'Tag %s does not resolve to the requested commit.\n' "$tag" >&2
        exit 1
    fi
fi

mkdir -p var dist
stage="$(mktemp -d "$root/var/package.XXXXXX")"
tar_temporary="$root/dist/.torah-${version}.tar.gz.tmp"
zip_temporary="$root/dist/.torah-${version}.zip.tmp.zip"
checksums_temporary="$root/dist/.SHA256SUMS.txt.tmp"

cleanup() {
    if [[ -n "${stage:-}" && "$stage" == "$root"/var/package.* && -d "$stage" ]]; then
        rm -rf -- "$stage"
    fi
    rm -f -- "$tar_temporary" "$zip_temporary" "$checksums_temporary"
}
trap cleanup EXIT

manifest="$stage/package-files.txt"
git show "${commit}:tools/package-files.txt" > "$manifest"
mapfile -t allowlist < <(sed '/^[[:space:]]*#/d; /^[[:space:]]*$/d' "$manifest")
if [[ "${#allowlist[@]}" -eq 0 ]]; then
    printf 'Production package manifest is empty.\n' >&2
    exit 1
fi

mkdir -p "$stage/torah"
git archive --format=tar "$commit" -- "${allowlist[@]}" \
    | tar --extract --file=- --directory="$stage/torah"

source_date_epoch="$(git show --no-patch --format=%ct "$commit")"
find "$stage" -exec touch --date="@${source_date_epoch}" {} +

tar_archive="$root/dist/torah-${version}.tar.gz"
zip_archive="$root/dist/torah-${version}.zip"
checksums="$root/dist/SHA256SUMS.txt"

tar --sort=name \
    --owner=0 --group=0 --numeric-owner \
    --mode='a-x,a+r,u+w,go-w,a+X' \
    --mtime="@${source_date_epoch}" \
    --format=gnu \
    --directory="$stage" \
    --create --file=- torah \
    | gzip -n -9 > "$tar_temporary"
mv "$tar_temporary" "$tar_archive"

(
    cd "$stage"
    python3 "$root/tools/create-zip.py" torah "$zip_temporary" "$source_date_epoch"
)
mv "$zip_temporary" "$zip_archive"

TORAH_PACKAGE_MANIFEST="$manifest" tools/validate-package.sh "$tar_archive"
TORAH_PACKAGE_MANIFEST="$manifest" tools/validate-package.sh "$zip_archive"

(
    cd dist
    sha256sum "$(basename "$tar_archive")" "$(basename "$zip_archive")" > "$(basename "$checksums_temporary")"
)
mv "$checksums_temporary" "$checksums"

printf 'Created production packages from %s (%s):\n' "$ref" "$commit"
printf '  %s\n  %s\n  %s\n' "$tar_archive" "$zip_archive" "$checksums"
printf 'Package contents:\n'
tar --list --gzip --file="$tar_archive"
