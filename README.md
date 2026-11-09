# Cbox Status

Cbox Status for Laravel. Read a Cbox Status page's public JSON from any Laravel app, and
show its active incidents, maintenance and emergency notices as banners in your own
layout.

- Typed value objects for `current.json` (contract `cbox-status/v1`)
- The page's own origin first, then fallback mirrors
- Freshness from the heartbeat's age
- Ed25519 emergency notices, verified with libsodium against keys you configure
- A short cache with stale-if-error
- `<x-cbox-status::banner />` for Blade, `Status::shared()` for Inertia
- `FakeStatus` and `InteractsWithStatus` for your tests
- `php artisan status:check`

It reads only public files. It has no API key and never talks to the Cbox Status
control plane.

## Install

```bash
composer require cboxdk/laravel-status-client
```

```dotenv
CBOX_STATUS_PAGE=01m708vt2qwrtpzk61e80vaby8
CBOX_STATUS_URL=https://cbox.cboxstatus.com
CBOX_STATUS_FALLBACKS=https://data.cboxstatus.com
# Optional: public keys from the page's "Emergency keys"
CBOX_STATUS_EMERGENCY_KEYS=
```

```blade
<x-cbox-status::banner />
```

```php
Inertia::share('status', fn () => \Cbox\StatusClient\Facades\Status::shared());
```

```php
$report = Status::report();

$report->available();          // a snapshot was read (now or from the cache)
$report->stale;                // the heartbeat is older than stale_after_seconds
$report->indicator();          // ComponentStatus::Operational, …
$report->activeIncidents();    // list<Incident>
$report->emergency?->message;  // a verified emergency notice
```

## Testing

```php
use Cbox\StatusClient\Snapshot\IncidentImpact;

$this->fakeStatus()->withIncident('Sign-in is slow', IncidentImpact::Minor);
```

## Documentation

[docs/index.md](docs/index.md). Start with the [Quickstart](docs/quickstart.md).

## Quality gate

```bash
composer qa   # pint, phpstan (max), pest, license check, audit, SBOM drift
```

## License

MIT. See [LICENSE.md](LICENSE.md).
