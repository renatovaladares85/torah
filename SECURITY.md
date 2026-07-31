# Security Policy

## Supported Versions

Only the latest released Torah version is supported with security fixes.

## Reporting A Vulnerability

Use GitHub private vulnerability reporting for this repository. Do not disclose
security vulnerabilities, credentials, internal URLs, logs, ticket content, or
personal data in a public issue.

Include only the minimum synthetic reproduction needed. Replace all names,
addresses, identifiers, hostnames, and organization details with fictitious
values.

## Security Model

- GLPI authorization is authoritative; Torah never grants access.
- JavaScript is not a security boundary.
- Backend hooks enforce only restrictions whose Backend option is selected;
  legacy policies retain their restrictive backend interpretation.
- Disabled Torah code does not affect GLPI behavior.
- Audit records exclude ticket content, actor details, and changed values.
