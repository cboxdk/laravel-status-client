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
holding a value of the wrong type, and ignores keys it does not know, because v1 only
grows.

## Unknown values

v1 can also grow by adding a new value to a status, state or impact. The client reads
such a page instead of skipping it:

| Field | Unknown value becomes | Raw string |
|-------|-----------------------|------------|
| `status.indicator` | `ComponentStatus::Unknown` | `OverallStatus::$indicatorValue` |
| component and update `status` | `ComponentStatus::Unknown` | `$statusValue` |
| incident and update `state` | `IncidentState::Unknown` | `$stateValue` |
| incident `impact` | `IncidentImpact::Unknown` | `$impactValue` |

`Unknown` is never treated as operational: `allClear()` is false, and it ranks with
degraded performance. An unknown impact shows as a warning banner; an unknown state is
labelled with its own words ("Postmortem"). `toArray()` writes the raw strings back.

The test suite validates its fixtures against a copy of the contract's JSON Schema, and
reads `current-unknown.json`, a page with values this client does not know.
