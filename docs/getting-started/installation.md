---
title: Installation
description: Install the package and set the environment keys.
weight: 11
---

# Installation

```bash
composer require cboxdk/laravel-status-client
```

The service provider and the `Status` facade are discovered automatically.

## Environment

| Key | Required | Example |
|-----|----------|---------|
| `CBOX_STATUS_PAGE` | yes | `01m708vt2qwrtpzk61e80vaby8` |
| `CBOX_STATUS_URL` | yes | `https://cbox.cboxstatus.com` (the page's own domain or custom domain) |
| `CBOX_STATUS_FALLBACKS` | no | `https://data.cboxstatus.com` (comma separated) |
| `CBOX_STATUS_EMERGENCY_KEYS` | no | base64 public keys from the page's "Emergency keys", comma separated |
| `CBOX_STATUS_COMPONENTS` | no | component ids this app cares about, comma separated |
| `CBOX_STATUS_CACHE_STORE` | no | a cache store name; the default store otherwise |
| `CBOX_STATUS_CACHE_TTL` | no | seconds, default `30` |

Without `CBOX_STATUS_PAGE` and `CBOX_STATUS_URL` the package binds a null reader: no
request is made and no banner shows. That keeps local and CI environments quiet.

## Config file

```bash
php artisan vendor:publish --tag=cbox-status-config
```

See the [configuration reference](../configuration/reference.md). To change the banner
markup, publish the view with `--tag=cbox-status-views`.
