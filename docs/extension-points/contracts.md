---
title: Contracts
description: Rebind the reader, the verifier or the banner policy.
weight: 41
---

# Contracts

Every capability is an interface in `Cbox\StatusClient\Contracts`, bound in
`StatusClientServiceProvider`. Rebind any of them in your own provider.

## `Banners`

Change what is shown or how it is worded:

```php
use Cbox\StatusClient\Banners\DefaultBanners;
use Cbox\StatusClient\Contracts\Banners;

$this->app->bind(Banners::class, fn () => new class extends DefaultBanners {
    public function for(StatusReport $report, CarbonInterface $now): array
    {
        // Only incidents and the emergency notice, no maintenance.
        return array_values(array_filter(
            parent::for($report, $now),
            fn (Banner $b) => in_array($b->kind, [BannerKind::Emergency, BannerKind::Incident], true),
        ));
    }
});
```

## `StatusReader`

Read from somewhere else, for example a snapshot your own job stores. It must not
throw; return `StatusReport::unavailable()` when nothing is known.

## `EmergencyVerifier`

Replace verification, for example to load trusted keys from your database. Keep the
checks in [Emergency notices](../core-concepts/emergency-notices.md); a verifier that
returns a notice it did not verify defeats the point of signing.

## View

`php artisan vendor:publish --tag=cbox-status-views` copies
`components/banner.blade.php` into `resources/views/vendor/cbox-status`.
