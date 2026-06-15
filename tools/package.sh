#!/usr/bin/env bash

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"

tools/check-public-repo.sh
tools/check-version.sh

version="$(sed -n "s/^define('PLUGIN_TORAH_VERSION', '\([^']*\)');$/\1/p" setup.php)"
mkdir -p dist
archive="dist/torah-${version}.tar.gz"

git archive --format=tar --prefix=torah/ HEAD | gzip -9 > "$archive"

if tar -tzf "$archive" | rg -q '(^|/)(vendor|node_modules|var|tests|tools|docs/prompts)(/|$)'; then
    printf 'Package contains development-only files.\n' >&2
    exit 1
fi

printf 'Created %s\n' "$archive"
