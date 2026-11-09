<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Freshness;
use Cbox\StatusClient\Snapshot\Heartbeat;

it('is stale without a heartbeat, or when it is older than the limit', function (): void {
    $now = CarbonImmutable::parse('2026-11-10T10:00:00Z');

    expect(Freshness::isStale(null, 300, $now))->toBeTrue()
        ->and(Freshness::isStale(Heartbeat::fromArray(heartbeat('2026-11-10T09:56:00Z')), 300, $now))->toBeFalse()
        ->and(Freshness::isStale(Heartbeat::fromArray(heartbeat('2026-11-10T09:55:00Z')), 300, $now))->toBeFalse()
        ->and(Freshness::isStale(Heartbeat::fromArray(heartbeat('2026-11-10T09:54:59Z')), 300, $now))->toBeTrue();
});

it('reads only cbox-status/v1 heartbeats', function (): void {
    expect(Heartbeat::fromArray(['schema' => 'cbox-status/v2', 'written_at' => '2026-11-10T09:59:00Z']))->toBeNull()
        ->and(Heartbeat::fromArray(['schema' => 'cbox-status/v1', 'written_at' => 'yesterday']))->toBeNull()
        ->and(Heartbeat::fromArray(heartbeat('2026-11-10T09:59:00+01:00'))?->writtenAt->toIso8601ZuluString())->toBe('2026-11-10T08:59:00Z');
});
