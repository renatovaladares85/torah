# Privacy And Public Repository Policy

This repository is public. Source, tests, documentation, issues, pull requests,
build logs, release packages, and commit history must contain synthetic data
only.

## Prohibited Material

- Real ticket titles, descriptions, attachments, comments, or identifiers.
- Names, personal e-mail addresses, phone numbers, account names, or employee
  identifiers.
- Organization names, internal hostnames, private URLs, network topology, or
  infrastructure addresses.
- Credentials, API tokens, cookies, session values, private keys, database
  connection strings, dumps, backups, or production logs.
- Local filesystem paths or screenshots containing private information.

## Safe Test Data

- E-mail domains: `example.test`, `example.com`, or `example.org`.
- IPv4 ranges: `192.0.2.0/24`, `198.51.100.0/24`, or `203.0.113.0/24`.
- IPv6 range: `2001:db8::/32`.
- Generic identities such as `Example User`, `Example Entity`, and
  `Example Profile`.

## Required Checks

Run `tools/check-public-repo.sh` before every commit, package, or push. A human
review must still verify context because automated scanners cannot prove that
all data is synthetic.

Torah audit events use only policy, profile, entity, ticket, origin and hook
identifiers. They never include ticket content, actor details, values or full
request payloads.
