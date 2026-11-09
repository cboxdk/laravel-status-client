---
title: Testing
description: Fake the status page with FakeStatus and the InteractsWithStatus trait, as the package's own suite does.
weight: 12
---

# Testing

`Cbox\StatusClient\Testing\FakeStatus` is a status page in memory. It starts
operational and fresh. `InteractsWithStatus::fakeStatus()` binds it as the
`StatusReader`, so the facade, the Blade component, `Status::shared()` and
`status:check` all read it. Nothing is fetched or cached.

```php
use Cbox\StatusClient\Testing\InteractsWithStatus;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithStatus;
}
```

```php
use Cbox\StatusClient\Snapshot\IncidentImpact;

it('shows the incident banner', function () {
    $this->fakeStatus()->withIncident('Sign-in is slow', IncidentImpact::Minor);

    $this->get('/dashboard')->assertSee('Sign-in is slow');
});
```

## Builders

| Method | Effect |
|--------|--------|
| `withIncident($title, $impact, $body, $state, $components, $id)` | An active incident with one update. |
| `withMaintenance($title, $from, $until, $body, $components, $id)` | Active if the window contains now, upcoming if it starts later. |
| `withEmergency($message, $expiresAt)` | A verified emergency notice. |
| `withIndicator($status, $description)` | The page-wide indicator. |
| `withComponent($id, $name, $status)` | A component. |
| `withSnapshot(Snapshot $snapshot)` | Replace the whole snapshot. |
| `FakeStatus::fromFile($path)` | Serve a saved `current.json`. |
| `stale()` | The heartbeat is an hour old. |
| `unavailable()` | No origin answered. An emergency notice still shows. |

## Assertions

| Method | Checks |
|--------|--------|
| `assertRead(?int $times = null)` | The page was read (exactly `$times` times). |
| `assertNotRead()` | The page was not read at all. |
| `reads(): int` | The count, for your own assertions. |

## Testing against the network shape

To test fallback and caching end to end, use Laravel's `Http::fake()`. The package's
own `tests/Feature/HttpStatusReaderTest.php` does this.
