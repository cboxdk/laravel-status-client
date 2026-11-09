# Security Policy

## Supported versions

The latest minor of the current major receives security fixes.

## Reporting a vulnerability

Please do not open a public issue. Use GitHub's private vulnerability reporting on this
repository, or email [sn@cbox.dk](mailto:sn@cbox.dk) with a description and, if
possible, a proof of concept. Reports are handled on a best-effort basis; there is no
fixed response time.

Areas of particular interest for this package:

- Emergency notice verification: a notice must never show unless its Ed25519 signature
  verifies against a configured key, it names the configured page, and it has not expired.
- Banner output: every string from the status JSON is untrusted and must render as text;
  links must stay `http(s)`.
- Origin handling: the client must never follow a link in the JSON to another host.

See [docs/security/threat-model.md](docs/security/threat-model.md).
