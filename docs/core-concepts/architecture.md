---
title: Architecture
description: The reader, the cache, the verifier, the banner policy and the facade.
weight: 21
---

# Architecture

```
StatusClient (facade: Status) ── Banners (DefaultBanners)
        │
StatusReader (HttpStatusReader | NullStatusReader | FakeStatus)
        │            │
   Laravel HTTP   cache store ── EmergencyVerifier (SodiumEmergencyVerifier)
        │
 https://{page domain}/p/{page}/current.json, /heartbeat.json, /p/{page}/emergency.json
 https://{mirror}/…   (same paths)
```

- **`Contracts\StatusReader`** reads and caches. `read()` uses the cache;
  `refresh()` fetches now. Neither throws.
- **`Contracts\EmergencyVerifier`** decides whether a notice is genuine.
- **`Contracts\Banners`** turns a `StatusReport` into a list of `Banner`.
- **`StatusClient`** is what an app calls. `report()`, `refresh()`, `banners()`,
  `shared()`.

## Values

`StatusReport` holds a `Snapshot` (or null), the `stale` flag, the heartbeat time, a
verified `EmergencyNotice` (or null), the origin it came from, and `fetchFailed`.
`Snapshot` is `current.json` as typed values: `Page`, `OverallStatus`, `Group`,
`Component`, `Incident` with `IncidentUpdate`s, `IncidentSummary`, `Maintenance`, and
the enums `ComponentStatus`, `IncidentState`, `IncidentImpact`.

Arrays exist only at the edges: decoded JSON on the way in (`fromArray`) and
`toArray()` for Inertia or a JSON response.

## Contract

The parser follows `cbox-status/v1`. It refuses a document missing a required key or
holding a value outside an enum, and ignores keys it does not know, because v1 only
grows. The test suite validates its fixtures against a copy of the contract's JSON
Schema.
