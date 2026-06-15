#!/usr/bin/env python3

from __future__ import annotations

import ast
import gettext
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parent.parent
SOURCE_GLOBS = ("*.php", "*.twig")
DOMAIN_PATTERN = re.compile(r"__\(\s*(['\"])(.*?)\1\s*,\s*(['\"])torah\3\s*\)")


def source_messages() -> set[str]:
    messages: set[str] = set()
    for pattern in SOURCE_GLOBS:
        for path in ROOT.rglob(pattern):
            if any(part in {"vendor", "var"} for part in path.parts):
                continue
            text = path.read_text(encoding="utf-8")
            for match in DOMAIN_PATTERN.finditer(text):
                messages.add(ast.literal_eval(match.group(1) + match.group(2) + match.group(1)))
    return messages


def po_messages(path: Path) -> dict[str, str]:
    entries: dict[str, str] = {}
    current_id: str | None = None
    current_value: str | None = None
    for line in path.read_text(encoding="utf-8").splitlines():
        if line.startswith("msgid "):
            current_id = ast.literal_eval(line[6:])
            current_value = None
        elif line.startswith("msgstr ") and current_id is not None:
            current_value = ast.literal_eval(line[7:])
            if current_id:
                entries[current_id] = current_value
    return entries


source = source_messages()
pot = set(po_messages(ROOT / "locales" / "torah.pot"))
translations = po_messages(ROOT / "locales" / "pt_BR.po")

if source != pot:
    raise SystemExit(f"POT mismatch. Missing={sorted(source - pot)} Extra={sorted(pot - source)}")
if source != set(translations):
    raise SystemExit("pt_BR catalog does not contain exactly the source messages.")
if any(not value for value in translations.values()):
    raise SystemExit("pt_BR catalog contains an empty translation.")

with (ROOT / "locales" / "pt_BR.mo").open("rb") as stream:
    catalog = gettext.GNUTranslations(stream)
for message, translated in translations.items():
    if catalog.gettext(message) != translated:
        raise SystemExit(f"MO mismatch for {message!r}")

print(f"Validated {len(source)} translated messages.")
