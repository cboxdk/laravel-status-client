# Contract fixtures

- `current.v1.schema.json`: copied from `cboxdk/cbox-status` `contracts/`. The public contract
  `cbox-status/v1`; refresh it when the contract grows (additive only in v1).
- `emergency-go.json`: copied from `cboxdk/cbox-status` `shell/test/fixtures/`. A notice signed by
  the Go emergency CLI. The same file proves the TypeScript shell and the PHP importer.
- `current-live.json`: `https://cbox.cboxstatus.com/p/01m708vt2qwrtpzk61e80vaby8/current.json`,
  saved 2026-11-09.
- `current-incident.json`: hand-written, every optional branch filled in. Validated against the
  schema by `tests/Unit/ContractTest.php`.
