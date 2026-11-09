<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Contracts\StatusReader;
use Cbox\StatusClient\Facades\Status;
use Cbox\StatusClient\Http\HttpStatusReader;
use Cbox\StatusClient\Http\NullStatusReader;
use Cbox\StatusClient\Tests\TestCase;

const CURRENT = TestCase::PRIMARY.'/p/01pageid/current.json';
const BEAT = TestCase::PRIMARY.'/heartbeat.json';
const MIRROR_CURRENT = TestCase::MIRROR.'/p/01pageid/current.json';
const MIRROR_BEAT = TestCase::MIRROR.'/heartbeat.json';

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-10T10:00:00Z'));
});

it('binds the HTTP reader when configured and a null reader when not', function (): void {
    expect(app(StatusReader::class))->toBeInstanceOf(HttpStatusReader::class);

    config(['cbox-status.url' => 'ftp://nope.test']);

    expect(app(StatusReader::class))->toBeInstanceOf(NullStatusReader::class)
        ->and(Status::report()->available())->toBeFalse()
        ->and(Status::report()->fetchFailed)->toBeFalse();
});

it('uses the primary origin when it is fresh and never asks the mirror', function (): void {
    $this->network([CURRENT => snapshot(), BEAT => heartbeat('2026-11-10T09:59:30Z')]);

    $report = Status::refresh();

    expect($report->origin)->toBe(TestCase::PRIMARY)
        ->and($report->stale)->toBeFalse()
        ->and($report->heartbeatAge())->toBe(30)
        ->and($report->snapshot?->page->name)->toBe('Acme')
        ->and($this->requested())->toBe([CURRENT, BEAT]);
});

it('falls back when the primary is down', function (): void {
    $this->network([
        CURRENT => 'down',
        MIRROR_CURRENT => snapshot(),
        MIRROR_BEAT => heartbeat('2026-11-10T09:59:30Z'),
    ]);

    $report = Status::refresh();

    expect($report->origin)->toBe(TestCase::MIRROR)
        ->and($report->stale)->toBeFalse()
        ->and($report->fetchFailed)->toBeFalse();
});

it('prefers the newest snapshot when every origin is stale', function (): void {
    $this->network([
        CURRENT => snapshot('2026-11-10T08:00:00Z'),
        MIRROR_CURRENT => snapshot('2026-11-10T09:00:00Z'),
    ]);

    $report = Status::refresh();

    expect($report->origin)->toBe(TestCase::MIRROR)
        ->and($report->stale)->toBeTrue()
        ->and($report->heartbeatAge())->toBeNull();
});

it('moves on from a stale primary to a fresh mirror', function (): void {
    $this->network([
        CURRENT => snapshot('2026-11-10T09:00:00Z'),
        BEAT => heartbeat('2026-11-10T09:00:00Z'),
        MIRROR_CURRENT => snapshot('2026-11-10T08:00:00Z'),
        MIRROR_BEAT => heartbeat('2026-11-10T09:59:00Z'),
    ]);

    expect(Status::refresh()->origin)->toBe(TestCase::MIRROR);
});

it('ignores a snapshot for another page and one that breaks the contract', function (): void {
    $broken = snapshot();
    unset($broken['components']);

    $this->network([CURRENT => snapshot(page: 'someone-else'), MIRROR_CURRENT => $broken]);

    $report = Status::refresh();

    expect($report->available())->toBeFalse()
        ->and($report->fetchFailed)->toBeTrue();
});

it('tries fallback origins the snapshot advertises after the configured ones', function (): void {
    config(['cbox-status.fallbacks' => '']);
    $this->network([
        CURRENT => snapshot('2026-11-10T08:00:00Z', fallbacks: ['https://mirror-b.test/', 'javascript:alert(1)']),
        'https://mirror-b.test/p/01pageid/current.json' => snapshot(),
        'https://mirror-b.test/heartbeat.json' => heartbeat('2026-11-10T09:59:00Z'),
    ]);

    expect(Status::refresh()->origin)->toBe('https://mirror-b.test');
});

it('never follows a heartbeat link to another host', function (): void {
    $current = snapshot();
    $current['links']['heartbeat'] = '//evil.test/heartbeat.json';
    $this->network([CURRENT => $current, BEAT => heartbeat('2026-11-10T09:59:30Z')]);

    expect(Status::refresh()->stale)->toBeFalse()
        ->and($this->requested())->toBe([CURRENT, BEAT]);
});

it('serves reads from the cache within the TTL', function (): void {
    $this->network([CURRENT => snapshot(), BEAT => heartbeat('2026-11-10T09:59:30Z')]);

    Status::report();
    Status::report();
    $this->travel(20)->seconds();
    $report = Status::report();

    expect($this->requested())->toHaveCount(2)
        ->and($report->heartbeatAge())->toBe(50);

    $this->travel(15)->seconds();
    Status::report();

    expect($this->requested())->toHaveCount(4);
});

it('serves the last known snapshot when every origin fails, and judges it by its heartbeat', function (): void {
    $this->network([CURRENT => snapshot(), BEAT => heartbeat('2026-11-10T09:59:30Z')]);
    expect(Status::report()->stale)->toBeFalse();

    $this->routes = [CURRENT => 'down', MIRROR_CURRENT => 'down'];
    $this->travel(31)->seconds();
    $report = Status::report();

    expect($report->available())->toBeTrue()
        ->and($report->fetchFailed)->toBeTrue()
        ->and($report->stale)->toBeFalse()
        ->and($report->snapshot?->page->name)->toBe('Acme');

    // The failure is remembered for error_ttl, so a down page is not hammered.
    $count = count($this->requested());
    Status::report();
    expect($this->requested())->toHaveCount($count);

    // Ten minutes on, the same snapshot is stale: the heartbeat has not moved.
    $this->travel(10)->minutes();
    expect(Status::report()->stale)->toBeTrue();
});

it('is unavailable when nothing answers and nothing is cached', function (): void {
    $this->network([CURRENT => 'down', MIRROR_CURRENT => 'down']);

    $report = Status::report();

    expect($report->available())->toBeFalse()
        ->and($report->fetchFailed)->toBeTrue()
        ->and(Status::banners())->toBe([]);
});

it('shows the Go-signed emergency notice from a mirror when the key is trusted', function (): void {
    $fixture = fixtureJson('emergency-go.json');
    config(['cbox-status.emergency_keys' => $fixture['public_key']]);
    $this->network([
        CURRENT => snapshot(),
        BEAT => heartbeat('2026-11-10T09:59:30Z'),
        TestCase::MIRROR.'/p/01pageid/emergency.json' => is_array($fixture['envelope']) ? $fixture['envelope'] : [],
    ]);

    $report = Status::refresh();

    expect($report->emergency?->message)->toBe('Signed in Go, verified in the browser.')
        ->and(Status::banners()[0]->body)->toBe('Signed in Go, verified in the browser.');
});

it('does not read emergency notices without trusted keys, and drops untrusted ones', function (): void {
    $fixture = fixtureJson('emergency-go.json');
    $envelope = is_array($fixture['envelope']) ? $fixture['envelope'] : [];
    $this->network([CURRENT => snapshot(), BEAT => heartbeat('2026-11-10T09:59:30Z'), TestCase::PRIMARY.'/p/01pageid/emergency.json' => $envelope]);

    expect(Status::refresh()->emergency)->toBeNull()
        ->and($this->requested())->not->toContain(TestCase::PRIMARY.'/p/01pageid/emergency.json');

    config(['cbox-status.emergency_keys' => base64_encode(str_repeat('k', 32))]);

    expect(Status::refresh()->emergency)->toBeNull();
});

it('keeps an emergency notice when every snapshot origin is down', function (): void {
    $fixture = fixtureJson('emergency-go.json');
    config(['cbox-status.emergency_keys' => $fixture['public_key']]);
    $this->network([
        CURRENT => 'down',
        MIRROR_CURRENT => 'down',
        TestCase::MIRROR.'/p/01pageid/emergency.json' => is_array($fixture['envelope']) ? $fixture['envelope'] : [],
    ]);

    $report = Status::report();

    expect($report->available())->toBeFalse()
        ->and($report->emergency?->pageId)->toBe('01pageid');
});
