# Changelog

## Unreleased

- First version: typed `cbox-status/v1` snapshot, origin fallback, heartbeat freshness,
  Ed25519 emergency notices (sodium), cache with stale-if-error, Blade banner component,
  `Status::shared()` for Inertia, `FakeStatus` + `InteractsWithStatus`, `status:check`.
- Unknown status, state and impact values (added to v1 after this release) are read as
  `Unknown` with the raw string kept, instead of making the page unreadable.
