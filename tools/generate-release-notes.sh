#!/usr/bin/env bash

set -euo pipefail

root="${TORAH_RELEASE_NOTES_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$root"

usage() {
    printf 'Usage: %s --tag v<SemVer> --version <SemVer> --date YYYY-MM-DD --repository <owner/repository> --checksums <SHA256SUMS.txt> --changelog-pt-br <file> --output <file>\n' "${0##*/}" >&2
}

tag=""
version=""
release_date=""
repository=""
checksums=""
changelog_pt_br=""
output=""

while [[ "$#" -gt 0 ]]; do
    case "$1" in
        --tag|--version|--date|--repository|--checksums|--changelog-pt-br|--output)
            option="$1"
            shift
            if [[ "$#" -eq 0 ]]; then
                usage
                exit 1
            fi
            case "$option" in
                --tag) tag="$1" ;;
                --version) version="$1" ;;
                --date) release_date="$1" ;;
                --repository) repository="$1" ;;
                --checksums) checksums="$1" ;;
                --changelog-pt-br) changelog_pt_br="$1" ;;
                --output) output="$1" ;;
            esac
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            usage
            exit 1
            ;;
    esac
    shift
done

semver_pattern='^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?(\+[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?$'
if [[ "$tag" != v* || ! "${tag#v}" =~ $semver_pattern ]]; then
    printf 'Release tag must use v<SemVer>: %s\n' "$tag" >&2
    exit 1
fi

tag_version="${tag#v}"
if [[ -z "$version" || "$version" != "$tag_version" ]]; then
    printf 'Version mismatch: tag=%s version=%s\n' "$tag" "$version" >&2
    exit 1
fi

if [[ ! "$release_date" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}$ ]] || ! date --date="$release_date" +%F >/dev/null 2>&1; then
    printf 'Release date must use a valid YYYY-MM-DD value: %s\n' "$release_date" >&2
    exit 1
fi

if [[ ! "$repository" =~ ^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$ ]]; then
    printf 'Repository must use owner/repository format.\n' >&2
    exit 1
fi

setup_version="$(sed -n "s/^define('PLUGIN_TORAH_VERSION', '\([^']*\)');$/\1/p" setup.php)"
xml_version="$(sed -n 's/.*<num>\([^<]*\)<\/num>.*/\1/p' plugin.xml | head -n1)"
if [[ "$setup_version" != "$version" || "$xml_version" != "$version" ]]; then
    printf 'Metadata version mismatch: setup.php=%s plugin.xml=%s expected=%s\n' "$setup_version" "$xml_version" "$version" >&2
    exit 1
fi

if [[ ! -f "$checksums" ]]; then
    printf 'Checksum file is unavailable: %s\n' "$checksums" >&2
    exit 1
fi
if [[ ! -f "$changelog_pt_br" ]]; then
    printf 'Brazilian Portuguese changelog is unavailable: %s\n' "$changelog_pt_br" >&2
    exit 1
fi

assets_dir="$(cd "$(dirname "$checksums")" && pwd)"
tar_name="torah-${version}.tar.gz"
zip_name="torah-${version}.zip"
for asset in "$tar_name" "$zip_name"; do
    if [[ ! -s "$assets_dir/$asset" ]]; then
        printf 'Expected production asset is unavailable: %s\n' "$asset" >&2
        exit 1
    fi
    if ! awk -v asset="$asset" '
        /^[0-9a-f]{64}  / {
            filename = $2
            sub(/^\*/, "", filename)
            if (filename == asset) {
                found = 1
            }
        }
        END { exit !found }
    ' "$checksums"; then
        printf 'Checksum is unavailable for production asset: %s\n' "$asset" >&2
        exit 1
    fi
done

if ! (cd "$assets_dir" && sha256sum --check --status "$(basename "$checksums")"); then
    printf 'Production asset checksum validation failed.\n' >&2
    exit 1
fi

section_en="$(mktemp)"
section_pt_br="$(mktemp)"
trap 'rm -f "$section_en" "$section_pt_br"' EXIT

extract_section() {
    local changelog="$1"
    local section="$2"
    local language="$3"
    local matches

    matches="$(awk -v heading="## [${version}] - " '
        index($0, heading) == 1 && length($0) > length(heading) { count++ }
        END { print count + 0 }
    ' "$changelog")"
    if [[ "$matches" -ne 1 ]]; then
        printf 'Expected exactly one %s changelog section for version %s.\n' "$language" "$version" >&2
        exit 1
    fi

    awk -v heading="## [${version}] - " '
        index($0, heading) == 1 && length($0) > length(heading) { capture = 1; next }
        capture && /^## \[/ { exit }
        capture { print }
    ' "$changelog" > "$section"

    if [[ -z "$(tr -d '[:space:]' < "$section")" ]]; then
        printf '%s changelog section for version %s is empty.\n' "$language" "$version" >&2
        exit 1
    fi
    if rg -qF '## [Unreleased]' "$section"; then
        printf '%s changelog extraction unexpectedly includes Unreleased content.\n' "$language" >&2
        exit 1
    fi
}

extract_section CHANGELOG.md "$section_en" English
extract_section "$changelog_pt_br" "$section_pt_br" 'Brazilian Portuguese'

mkdir -p "$(dirname "$output")"
temporary_output="$(mktemp "${output}.XXXXXX")"
cat > "$temporary_output" <<EOF
## [${version}] - ${release_date}

**GLPI validated:** 10.0.20<br>
**PHP:** >= $(sed -n "s/^define('PLUGIN_TORAH_MIN_PHP_VERSION', '\([^']*\)');$/\1/p" setup.php)<br>
**State:** Stable

## Changes

$(cat "$section_en")

## Alterações

$(cat "$section_pt_br")

## Compatibility / Compatibilidade

- Declared GLPI compatibility: >= $(sed -n "s/^define('PLUGIN_TORAH_MIN_GLPI_VERSION', '\([^']*\)');$/\1/p" setup.php) and < $(sed -n "s/^define('PLUGIN_TORAH_MAX_GLPI_VERSION', '\([^']*\)');$/\1/p" setup.php).
- Validated with: GLPI 10.0.20.
- Minimum PHP version: >= $(sed -n "s/^define('PLUGIN_TORAH_MIN_PHP_VERSION', '\([^']*\)');$/\1/p" setup.php).
- Compatibilidade GLPI declarada: >= $(sed -n "s/^define('PLUGIN_TORAH_MIN_GLPI_VERSION', '\([^']*\)');$/\1/p" setup.php) e < $(sed -n "s/^define('PLUGIN_TORAH_MAX_GLPI_VERSION', '\([^']*\)');$/\1/p" setup.php).
- Validado com: GLPI 10.0.20.
- Versão mínima do PHP: >= $(sed -n "s/^define('PLUGIN_TORAH_MIN_PHP_VERSION', '\([^']*\)');$/\1/p" setup.php).

## Production installation packages / Pacotes de instalação de produção

- \`${tar_name}\`
- \`${zip_name}\`
- \`SHA256SUMS.txt\`

Both production archives contain one \`torah/\` root directory. Os dois
arquivos de produção contêm uma única raiz \`torah/\`.

## Installation / Instalação

1. Download one production package asset.
2. Verify it with the attached \`SHA256SUMS.txt\` file.
3. Extract it into \`<GLPI_ROOT>/plugins\`.
4. Confirm the resulting directory is \`<GLPI_ROOT>/plugins/torah\`.
5. Install and activate Torah from the GLPI plugin administration page.

1. Baixe um asset de pacote de produção.
2. Verifique-o com o arquivo \`SHA256SUMS.txt\` anexado.
3. Extraia-o em \`<GLPI_ROOT>/plugins\`.
4. Confirme que o diretório resultante é \`<GLPI_ROOT>/plugins/torah\`.
5. Instale e ative Torah pela página de administração de plugins do GLPI.

## Changelog / Registro de alterações

See [CHANGELOG.md](https://github.com/${repository}/blob/${tag}/CHANGELOG.md) for the complete release history.
Consulte [CHANGELOG.pt_BR.md](https://github.com/${repository}/blob/${tag}/CHANGELOG.pt_BR.md) para a versão em português brasileiro.

## Integrity notice / Aviso de integridade

\`\`\`text
$(cat "$checksums")
\`\`\`

Do not install the automatically generated “Source code (zip)” or “Source code (tar.gz)” archives. They are repository snapshots and are not the validated production packages.
Não instale os arquivos gerados automaticamente “Source code (zip)” ou “Source code (tar.gz)”. Eles são snapshots do repositório e não são os pacotes de produção validados.
EOF
mv "$temporary_output" "$output"
