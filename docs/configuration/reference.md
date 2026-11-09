---
title: Reference
description: Every key in config/cbox-status.php, its environment variable and default.
weight: 51
---

# Configuration reference

| Key | Env | Default | |
|-----|-----|---------|--|
| `page` | `CBOX_STATUS_PAGE` | none | Page id, as in `/p/{page}/current.json`. Required. |
| `url` | `CBOX_STATUS_URL` | none | The page's origin. Required; `http(s)` only. |
| `fallbacks` | `CBOX_STATUS_FALLBACKS` | none | Origins tried after `url`, comma separated or an array. |
| `emergency_keys` | `CBOX_STATUS_EMERGENCY_KEYS` | none | Trusted base64 Ed25519 public keys. Empty: notices are not read. |
| `components` | `CBOX_STATUS_COMPONENTS` | none | Component ids for the banner filter. Empty: everything shows. |
| `cache.store` | `CBOX_STATUS_CACHE_STORE` | default store | |
| `cache.ttl` | `CBOX_STATUS_CACHE_TTL` | `30` | Seconds a fetch is reused. |
| `cache.error_ttl` | | `10` | Seconds a failed fetch is remembered. |
| `cache.keep` | | `86400` | Seconds the last good snapshot is kept for stale-if-error. |
| `http.timeout` | | `3` | Seconds per request. |
| `http.connect_timeout` | | `2` | Seconds to connect. |
| `banners.upcoming_within_hours` | | `48` | How early upcoming maintenance shows. |
| `banners.show_delayed` | | `false` | Show "may be delayed" for a stale page. |

Invalid values fall back to the default. An origin that is not an `http(s)` URL is dropped.
