#!/usr/bin/env bash

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
generator="$root/tools/generate-release-notes.sh"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

fixture="$work/fixture"
mkdir -p "$fixture/dist"

write_fixture() {
    local changelog="$1"
    local setup_version="${2:-0.4.9}"
    local xml_version="${3:-0.4.9}"
    printf "define('PLUGIN_TORAH_VERSION', '%s');\n" "$setup_version" > "$fixture/setup.php"
    printf "define('PLUGIN_TORAH_MIN_GLPI_VERSION', '10.0.20');\ndefine('PLUGIN_TORAH_MAX_GLPI_VERSION', '10.0.99');\ndefine('PLUGIN_TORAH_MIN_PHP_VERSION', '8.2.0');\n" >> "$fixture/setup.php"
    printf '<root><versions><version><num>%s</num></version></versions></root>\n' "$xml_version" > "$fixture/plugin.xml"
    printf '%s\n' "$changelog" > "$fixture/CHANGELOG.md"
    printf '%s\n' "${changelog//Changes/Alterações}" > "$fixture/CHANGELOG.pt_BR.md"
    printf 'tar package\n' > "$fixture/dist/torah-0.4.9.tar.gz"
    printf 'zip package\n' > "$fixture/dist/torah-0.4.9.zip"
    (cd "$fixture/dist" && sha256sum torah-0.4.9.tar.gz torah-0.4.9.zip > SHA256SUMS.txt)
}

run_generator() {
    (
        cd "$fixture"
        TORAH_RELEASE_NOTES_ROOT="$fixture" "$generator" --tag "$1" --version "$2" --repository renatovaladares85/torah --checksums dist/SHA256SUMS.txt --changelog-pt-br CHANGELOG.pt_BR.md --output "$fixture/release-notes.md"
    )
}

expect_failure() {
    if "$@" >/dev/null 2>&1; then
        printf 'Expected command to fail: %s\n' "$*" >&2
        exit 1
    fi
}

valid_changelog=$'# Changelog\n\n## [Unreleased]\n\n## [0.4.9] - 2026-08-03\n\n### Added\n\n- Keeps **Markdown** and special characters: & < >.\n\n## [0.4.8] - 2026-08-01\n\n### Fixed\n\n- Previous release.\n'
write_fixture "$valid_changelog"
run_generator v0.4.9 0.4.9
rg -q '^### Added$' "$fixture/release-notes.md"
rg -q '^## Alterações$' "$fixture/release-notes.md"
rg -q 'Keeps \*\*Markdown\*\* and special characters: & < >.' "$fixture/release-notes.md"
rg -q 'torah-0.4.9.tar.gz' "$fixture/release-notes.md"
if rg -q 'Previous release|## \[Unreleased\]' "$fixture/release-notes.md"; then
    printf 'Release notes extracted an adjacent changelog section.\n' >&2
    exit 1
fi

expect_failure run_generator 0.4.9 0.4.9
expect_failure run_generator v0.4.9 0.4.8
write_fixture $'# Changelog\n\n## [Unreleased]\n'
expect_failure run_generator v0.4.9 0.4.9
write_fixture $'# Changelog\n\n## [Unreleased]\n\n## [0.4.9] - 2026-08-03\n\n## [0.4.8] - 2026-08-01\n'
expect_failure run_generator v0.4.9 0.4.9
write_fixture "$valid_changelog" 0.4.8 0.4.9
expect_failure run_generator v0.4.9 0.4.9
write_fixture "$valid_changelog" 0.4.9 0.4.8
expect_failure run_generator v0.4.9 0.4.9
write_fixture "$valid_changelog"
mv "$fixture/dist/SHA256SUMS.txt" "$fixture/dist/SHA256SUMS.missing"
expect_failure run_generator v0.4.9 0.4.9
mv "$fixture/dist/SHA256SUMS.missing" "$fixture/dist/SHA256SUMS.txt"
mv "$fixture/dist/torah-0.4.9.zip" "$fixture/dist/wrong-name.zip"
expect_failure run_generator v0.4.9 0.4.9

validate_download_url() {
    local xml="$1"
    if ! rg -q '<download_url>https://github.com/renatovaladares85/torah/releases/download/v0.4.9/torah-0.4.9.tar.gz</download_url>' "$xml"; then
        return 1
    fi
    ! rg -q 'archive/refs/tags|Source code' "$xml"
}

missing_url="$work/missing-url.xml"
source_url="$work/source-url.xml"
valid_url="$work/valid-url.xml"
printf '<version><num>0.4.9</num></version>\n' > "$missing_url"
printf '<download_url>https://github.com/renatovaladares85/torah/archive/refs/tags/v0.4.9.tar.gz</download_url>\n' > "$source_url"
printf '<download_url>https://github.com/renatovaladares85/torah/releases/download/v0.4.9/torah-0.4.9.tar.gz</download_url>\n' > "$valid_url"
expect_failure validate_download_url "$missing_url"
expect_failure validate_download_url "$source_url"
validate_download_url "$valid_url"

printf 'Release notes regression tests passed.\n'
