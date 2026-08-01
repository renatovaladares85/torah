# Release Prompt

Prepare a Torah release only after explicit approval. Increment Semantic
Versioning for the approved delivery, synchronize all metadata, update the
changelog and traceability matrix, run the complete test and privacy suites,
build a package rooted at `torah/`, inspect its manifest, and exclude tests,
development dependencies, caches, local files, logs, dumps, and credentials.
Use a `v<SemVer>` tag only when it matches the declared plugin version. The
release workflow may create a draft with the validated ZIP, TAR.GZ, and
`SHA256SUMS.txt` assets; a human must review those assets and publish the
release explicitly. Do not push, tag, or publish without a separate explicit
approval.
