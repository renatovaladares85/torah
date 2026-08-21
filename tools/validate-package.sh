#!/usr/bin/env bash

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
archive="${1:-}"
manifest="${TORAH_PACKAGE_MANIFEST:-$root/tools/package-files.txt}"

if [[ -z "$archive" || ! -f "$archive" ]]; then
    printf 'Usage: %s <torah-version.tar.gz|torah-version.zip>\n' "${0##*/}" >&2
    exit 1
fi

archive="$(cd "$(dirname "$archive")" && pwd)/$(basename "$archive")"
if [[ ! -f "$manifest" ]]; then
    printf 'Production package manifest is unavailable.\n' >&2
    exit 1
fi
mkdir -p "$root/var"
work="$(mktemp -d "$root/var/package-validation.XXXXXX")"
trap 'rm -rf "$work"' EXIT
entries="$work/entries.txt"
actual_files="$work/actual-files.txt"
expected_files="$work/expected-files.txt"
mkdir -p "$work/extracted"
touch "$actual_files"

case "$archive" in
    *.tar.gz)
        gzip --test "$archive"
        tar --list --gzip --file="$archive" > "$entries"
        if tar --list --verbose --gzip --file="$archive" | awk '$1 !~ /^[-d]/ { found = 1 } END { exit !found }'; then
            printf 'Package contains an unsupported entry type.\n' >&2
            exit 1
        fi
        if tar --list --verbose --gzip --file="$archive" | awk '$1 ~ /^[lh]/ { found = 1 } END { exit !found }'; then
            printf 'Package contains an unexpected link.\n' >&2
            exit 1
        fi
        if tar --list --verbose --gzip --file="$archive" | awk '$1 ~ /^-/ && $1 ~ /x/ { found = 1 } END { exit !found }'; then
            printf 'Package contains an unnecessary executable file.\n' >&2
            exit 1
        fi
        if tar --list --verbose --gzip --file="$archive" | awk '($1 ~ /^-/ && $1 != "-rw-r--r--") || ($1 ~ /^d/ && $1 != "drwxr-xr-x") { found = 1 } END { exit !found }'; then
            printf 'Package contains unsafe file or directory permissions.\n' >&2
            exit 1
        fi
        ;;
    *.zip)
        unzip -tq "$archive"
        unzip -Z1 "$archive" > "$entries"
        if zipinfo -l "$archive" | awk '$1 ~ /^[bclps]/ { found = 1 } END { exit !found }'; then
            printf 'Package contains an unsupported entry type.\n' >&2
            exit 1
        fi
        if zipinfo -l "$archive" | awk '$1 ~ /^l/ { found = 1 } END { exit !found }'; then
            printf 'Package contains an unexpected link.\n' >&2
            exit 1
        fi
        if zipinfo -l "$archive" | awk '$1 ~ /^-/ && $1 ~ /x/ { found = 1 } END { exit !found }'; then
            printf 'Package contains an unnecessary executable file.\n' >&2
            exit 1
        fi
        if zipinfo -l "$archive" | awk '$1 ~ /^-/ && $1 != "-rw-r--r--" { found = 1 } END { exit !found }'; then
            printf 'Package contains unsafe file permissions.\n' >&2
            exit 1
        fi
        ;;
    *)
        printf 'Unsupported package format: %s\n' "$archive" >&2
        exit 1
        ;;
esac

file_count=0
while IFS= read -r entry; do
    if [[ -z "$entry" || "$entry" = /* || "$entry" = ../* || "$entry" = *'/../'* || "$entry" = *'//'* || "$entry" =~ [[:cntrl:]] ]]; then
        printf 'Package contains an unsafe path.\n' >&2
        exit 1
    fi
    if [[ "$entry" != torah && "$entry" != torah/ && "$entry" != torah/* ]]; then
        printf 'Package root must be torah/: %s\n' "$entry" >&2
        exit 1
    fi

    relative="${entry#torah/}"
    relative="${relative%/}"
    case "$relative" in
        .git|.git/*|.github|.github/*|.env|.env.*|tests|tests/*|test|test/*|node_modules|node_modules/*|coverage|coverage/*|vendor|vendor/*|phpunit.xml|phpunit.xml.dist|*.log|*.sql|*.dump|*.pem|*.key|id_rsa|id_ed25519)
            printf 'Package contains a prohibited path.\n' >&2
            exit 1
            ;;
    esac
    if [[ -z "$relative" || "$entry" = */ ]]; then
        continue
    fi

    printf '%s\n' "$entry" >> "$actual_files"
    file_count=$((file_count + 1))
done < "$entries"

sed '/^[[:space:]]*#/d; /^[[:space:]]*$/d; s#^#torah/#' "$manifest" \
    | LC_ALL=C sort > "$expected_files"
LC_ALL=C sort -o "$actual_files" "$actual_files"
if ! diff --unified "$expected_files" "$actual_files"; then
    printf 'Package contents differ from the explicit production manifest.\n' >&2
    exit 1
fi

case "$archive" in
    *.tar.gz)
        tar --extract --gzip --file="$archive" --directory="$work/extracted" --no-same-owner --no-same-permissions
        ;;
    *.zip)
        unzip -q "$archive" -d "$work/extracted"
        ;;
esac

if find "$work/extracted" -mindepth 1 -maxdepth 1 -not -name torah -print -quit | rg -q .; then
    printf 'Package extraction created content outside the torah root.\n' >&2
    exit 1
fi

package="$work/extracted/torah"
required_files=(setup.php hook.php plugin.xml LICENSE locales/pt_BR.mo torah.png)
required_directories=(src front ajax templates js)

for path in "${required_files[@]}"; do
    if [[ ! -s "$package/$path" ]]; then
        printf 'Required package file is missing or empty: %s\n' "$path" >&2
        exit 1
    fi
done
for path in "${required_directories[@]}"; do
    if [[ ! -d "$package/$path" ]]; then
        printf 'Required package directory is missing: %s\n' "$path" >&2
        exit 1
    fi
done

setup_version="$(sed -n "s/^define('PLUGIN_TORAH_VERSION', '\([^']*\)');$/\1/p" "$package/setup.php")"
xml_version="$(sed -n 's/.*<num>\([^<]*\)<\/num>.*/\1/p' "$package/plugin.xml" | head -n 1)"
expected_name="torah-${setup_version}"
case "$(basename "$archive")" in
    "${expected_name}.tar.gz"|"${expected_name}.zip") ;;
    *)
        printf 'Package filename does not match its internal version.\n' >&2
        exit 1
        ;;
esac
if [[ -z "$setup_version" || "$setup_version" != "$xml_version" ]]; then
    printf 'Package contains inconsistent version metadata.\n' >&2
    exit 1
fi

find "$package/src" "$package/front" "$package/ajax" -type f -name '*.php' -print0 \
    | xargs -0 -n1 php -l >/dev/null
php -l "$package/setup.php" >/dev/null
php -l "$package/hook.php" >/dev/null

if command -v xmllint >/dev/null 2>&1; then
    xmllint --noout "$package/plugin.xml"
else
    php -r '$document = new DOMDocument(); if (!$document->load($argv[1])) { exit(1); }' "$package/plugin.xml"
fi

if command -v msgunfmt >/dev/null 2>&1; then
    msgunfmt "$package/locales/pt_BR.mo" >/dev/null
else
    python3 -c 'import gettext, sys; gettext.GNUTranslations(open(sys.argv[1], "rb"))' "$package/locales/pt_BR.mo"
fi

if rg -q --hidden '(BEGIN (RSA |EC |OPENSSH |DSA )?PRIVATE KEY|gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{20,}|AKIA[0-9A-Z]{16})' "$package"; then
    printf 'Package security scan detected secret material.\n' >&2
    exit 1
fi
if rg -q --hidden "(password|passwd|secret|api[_-]?key|access[_-]?token)[[:space:]]*[:=][[:space:]]*[\"'][^\"']{4,}" "$package"; then
    printf 'Package security scan detected a credential assignment.\n' >&2
    exit 1
fi
if rg -q --hidden '([A-Za-z]:\\Users\\|/mnt/[a-z]/[Uu]sers/)' "$package"; then
    printf 'Package security scan detected a local filesystem path.\n' >&2
    exit 1
fi
if rg -q --hidden '(^|[^0-9])(10\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}|192\.168\.[0-9]{1,3}\.[0-9]{1,3}|172\.(1[6-9]|2[0-9]|3[01])\.[0-9]{1,3}\.[0-9]{1,3})([^0-9]|$)' "$package"; then
    printf 'Package security scan detected a private IPv4 address.\n' >&2
    exit 1
fi
email_matches="$(rg -n --hidden -e '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}' "$package" \
    | rg -v '@(example\.(com|org|net|test)|localhost)' || true)"
if [[ -n "$email_matches" ]]; then
    printf 'Package security scan detected a non-example e-mail address.\n' >&2
    exit 1
fi

check_reference() {
    local reference="$1"
    if [[ ! -f "$package/$reference" ]]; then
        printf 'Package contains a broken runtime reference: %s\n' "$reference" >&2
        exit 1
    fi
}

is_glpi_core_reference() {
    case "$1" in
        ajax/itemTicket.php|front/ticket.form.php) return 0 ;;
        *) return 1 ;;
    esac
}

while IFS= read -r reference; do
    [[ -n "$reference" ]] && check_reference "${reference#plugins/torah/}"
done < <(rg --no-filename -o 'plugins/torah/[A-Za-z0-9_./-]+' "$package/src" | sort -u)

while IFS= read -r reference; do
    [[ -n "$reference" ]] && check_reference "templates/${reference#@torah/}"
done < <(rg --no-filename -o '@torah/[A-Za-z0-9_./-]+' "$package/src" "$package/templates" | sort -u)

while IFS= read -r reference; do
    reference="${reference#/}"
    [[ -n "$reference" ]] && ! is_glpi_core_reference "$reference" && check_reference "$reference"
done < <(rg --no-filename -o '/(ajax|front)/[A-Za-z0-9_.-]+' "$package/src" | sort -u)

printf 'Validated %s production files in %s.\n' "$file_count" "$archive"
