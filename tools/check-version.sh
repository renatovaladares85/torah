#!/usr/bin/env bash

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"

setup_version="$(sed -n "s/^define('PLUGIN_TORAH_VERSION', '\([^']*\)');$/\1/p" setup.php)"
xml_version="$(sed -n 's/.*<num>\([^<]*\)<\/num>.*/\1/p' plugin.xml | head -n1)"

if [[ -z "$setup_version" || "$setup_version" != "$xml_version" ]]; then
    printf 'Version mismatch: setup.php=%s plugin.xml=%s\n' "$setup_version" "$xml_version" >&2
    exit 1
fi

semver_pattern='^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?(\+[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?$'
if [[ ! "$setup_version" =~ $semver_pattern ]]; then
    printf 'Version must use Semantic Versioning: %s\n' "$setup_version" >&2
    exit 1
fi

if ! rg -q "^## \[$setup_version\]" CHANGELOG.md; then
    printf 'Version %s is missing from CHANGELOG.md\n' "$setup_version" >&2
    exit 1
fi

printf 'Version %s is consistent.\n' "$setup_version"
