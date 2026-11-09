# Build status

What this package does today, and what it deliberately does not.

## Built and tested

- `current.json` (`cbox-status/v1`) parsed into readonly value objects and enums; strict on
  required keys and enum values, ignores unknown keys. Fixtures (the live Cbox page and a
  hand-written one covering every branch) are validated against a copy of the contract's
  JSON Schema, and `toArray()` round-trips them exactly.
- Origin fallback: primary, configured fallbacks, then the snapshot's `fallback_origins`.
  First fresh origin wins; otherwise the newest `generated_at`. Wrong page or invalid
  snapshot is skipped. `links.*` must be origin-relative.
- Freshness: heartbeat age against `stale_after_seconds`, re-judged on every read.
- Emergency notices: libsodium Ed25519, trusted keys from config, page and expiry checks.
  Verified against the Go CLI's signed fixture and against fresh real keypairs.
- Cache: TTL, stale-if-error with the last good snapshot, error TTL, cache failures ignored.
- Banners (`DefaultBanners`), Blade component `<x-cbox-status::banner />`,
  `Status::shared()` for Inertia, `status:check` (exit code as a probe).
- `FakeStatus` + `InteractsWithStatus`; the package's feature tests use them.

## Not built

- No stampede lock: concurrent cache misses each fetch. Schedule `status:check` to keep
  the cache warm instead.
- No incident detail fetch (`incidents.recent[].href`). Banners only need active items.
- No localisation of banner labels ("Important notice", "In progress", …). Rebind
  `Contracts\Banners` to change wording.
- Timestamps in the Blade component render in UTC; the page's `timezone` is not applied.
- No integrity check of `current.json` itself: the contract does not sign it.
