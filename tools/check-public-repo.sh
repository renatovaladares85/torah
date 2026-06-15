#!/usr/bin/env bash

set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"

failed=0

report_matches() {
    local label="$1"
    local pattern="$2"
    local output
    output="$(rg -n --hidden --glob '!.git/**' --glob '!vendor/**' --glob '!var/**' "$pattern" . || true)"
    if [[ -n "$output" ]]; then
        printf 'Privacy check failed: %s\n%s\n' "$label" "$output" >&2
        failed=1
    fi
}

invalid_name_one="Torh$(printf '\141')"
invalid_name_two="Tor$(printf '\303\241')"
local_windows_path="[A-Za-z]:\\\\Users\\\\"
local_wsl_path="/mnt/[a-z]/[Uu]sers/"

report_matches 'invalid product identity' "${invalid_name_one}|${invalid_name_two}"
report_matches 'private key material' 'BEGIN (RSA |EC |OPENSSH |DSA )?PRIVATE KEY'
report_matches 'common access token' '(gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{20,}|AKIA[0-9A-Z]{16})'
report_matches 'credential assignment' "(password|passwd|secret|api[_-]?key|access[_-]?token)[[:space:]]*[:=][[:space:]]*[\"'][^\"']{4,}"
report_matches 'local Windows path' "$local_windows_path"
report_matches 'local WSL path' "$local_wsl_path"
report_matches 'private IPv4 address' '(^|[^0-9])(10\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}|192\.168\.[0-9]{1,3}\.[0-9]{1,3}|172\.(1[6-9]|2[0-9]|3[01])\.[0-9]{1,3}\.[0-9]{1,3})([^0-9]|$)'

email_matches="$(rg -n --hidden --glob '!.git/**' --glob '!vendor/**' --glob '!var/**' -e '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}' . \
    | rg -v '@(example\.(com|org|net|test)|localhost)' || true)"
if [[ -n "$email_matches" ]]; then
    printf 'Privacy check failed: non-example e-mail address\n%s\n' "$email_matches" >&2
    failed=1
fi

while IFS= read -r -d '' path; do
    case "$path" in
        *.env|*.env.*|*.sql|*.sqlite|*.log|*.bak|*.pem|*.key|*.p12|*.pfx|*.zip|*.7z)
            printf 'Privacy check failed: forbidden artifact %s\n' "$path" >&2
            failed=1
            ;;
    esac
done < <(find . \( -path './.git' -o -path './vendor' -o -path './var' \) -prune -o -type f -print0)

if git ls-files | rg -q '^(vendor|node_modules)/'; then
    printf 'Privacy check failed: dependency directories must not be tracked.\n' >&2
    failed=1
fi

git diff --check

if [[ "$failed" -ne 0 ]]; then
    exit 1
fi

printf 'Public repository privacy checks passed.\n'
