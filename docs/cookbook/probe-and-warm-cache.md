---
title: Probe and warm the cache
description: Schedule status:check so no web request waits on a fetch, and use it as a probe.
weight: 33
---

# Probe and warm the cache

`status:check` fetches the page now, stores the result in the cache, and prints what it
found. Scheduled every minute, it keeps the cache warm, so a web request never waits on
the network:

```php
// routes/console.php
Schedule::command('status:check')->everyMinute()->withoutOverlapping();
```

Pair it with a TTL a little longer than the schedule (`CBOX_STATUS_CACHE_TTL=90`).

## As a probe

Exit code `0` when the page answered and is fresh, `1` otherwise.

```bash
php artisan status:check          # fetch now
php artisan status:check --cached # read through the cache
php artisan status:check --json   # machine-readable
```
