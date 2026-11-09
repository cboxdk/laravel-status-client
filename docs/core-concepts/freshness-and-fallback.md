---
title: Freshness and fallback
description: Which origin wins, when a page is stale, and what the cache does when everything is down.
weight: 22
---

# Freshness and fallback

## Order

1. The page's own origin (`CBOX_STATUS_URL`).
2. Each configured fallback, in order (`CBOX_STATUS_FALLBACKS`).
3. Origins the snapshot lists in `fallback_origins`, after the configured ones.

For each origin the reader fetches `/p/{page}/current.json`. A response that is not
`cbox-status/v1`, names another page, or breaks the contract is skipped. Otherwise it
fetches the heartbeat from the same origin (`links.heartbeat`, `/heartbeat.json`).

The first origin whose heartbeat is fresh wins. If none is fresh, the snapshot with
the newest `generated_at` wins, marked stale.

## Stale

A page is stale when there is no heartbeat, or the heartbeat is older than the
snapshot's `stale_after_seconds`. Exactly at the limit is still fresh. The heartbeat,
not `generated_at`, decides: a page with nothing new is not republished, but the
heartbeat keeps ticking while the control plane is up.

A stale page is not wrong, only possibly old. `StatusReport::$stale` says so; the
banners show "may be delayed" only when `banners.show_delayed` is on.

## Cache

| Key | Lifetime | Holds |
|-----|----------|-------|
| fresh | `cache.ttl` (30 s) | the last fetch |
| last | `cache.keep` (1 day) | the last fetch that found a snapshot |

When every origin fails, the reader serves `last` with `fetchFailed = true`, and
remembers that failure for `cache.error_ttl` (10 s) so a down page is not hammered. The
cached payload is the raw JSON; staleness and emergency expiry are judged again on every
read, so a cached page turns stale on its own.

A cache store that throws is ignored. The reader fetches instead.

## Timeouts

Each request has `http.timeout` (3 s) and `http.connect_timeout` (2 s). With two origins
down, a refresh can take several seconds. Schedule `status:check` every minute so web
requests read the cache: see [Probe and warm the cache](../cookbook/probe-and-warm-cache.md).
