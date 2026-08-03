# Release Prompt

Prepare a Torah release only after explicit approval. Increment Semantic
Versioning for the approved delivery, synchronize all metadata, update the
changelog and traceability matrix, run the complete test and privacy suites,
build a package rooted at `torah/`, inspect its manifest, and exclude tests,
development dependencies, caches, local files, logs, dumps, and credentials.
Use a `v<SemVer>` tag only when it matches the declared plugin version. The
release workflow may create a draft with the validated ZIP, TAR.GZ, and
`SHA256SUMS.txt` assets; a human must review those assets and publish the
release explicitly. A SemVer prerelease tag must create a draft marked as a
prerelease; a final-version tag must create a draft without that marker.
Generate future release notes from the matching, non-empty
English changelog section; do not use commit-generated notes as the source of
truth. Include only validated production-package instructions, checksums, and
the warning that GitHub source archives are not production packages. Keep
user-facing release communication available in English and `pt_BR`, with
English authoritative. Add a per-version `download_url` to catalog metadata
only after its public production asset, tag, checksum, and archive root have
been verified. Do not push, tag, or publish without a separate explicit
approval.
