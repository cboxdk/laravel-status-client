<?php

declare(strict_types=1);

use Cbox\StatusClient\Exceptions\InvalidSnapshot;
use Cbox\StatusClient\Snapshot\ComponentStatus;
use Cbox\StatusClient\Snapshot\IncidentImpact;
use Cbox\StatusClient\Snapshot\IncidentState;
use Cbox\StatusClient\Snapshot\Snapshot;
use Opis\JsonSchema\Validator;

function assertMatchesContract(mixed $document): void
{
    $validator = new Validator;
    $validator->resolver()?->registerFile('https://cboxstatus.com/schema/current.v1.json', contractFile('current.v1.schema.json'));
    $result = $validator->validate(json_decode((string) json_encode($document, JSON_THROW_ON_ERROR)), 'https://cboxstatus.com/schema/current.v1.json');

    expect($result->isValid())->toBeTrue((string) json_encode($result->error()?->info()));
}

it('has fixtures that match the contract', function (string $name): void {
    assertMatchesContract(fixtureJson($name));
})->with(['current-live.json', 'current-incident.json']);

it('reads the live page', function (): void {
    $snapshot = Snapshot::fromArray(fixtureJson('current-live.json'));

    expect($snapshot->page->id)->toBe('01m708vt2qwrtpzk61e80vaby8')
        ->and($snapshot->page->name)->toBe('Cbox')
        ->and($snapshot->status->indicator)->toBe(ComponentStatus::Operational)
        ->and($snapshot->components)->toHaveCount(6)
        ->and($snapshot->groups[0]->componentIds)->toHaveCount(6)
        ->and($snapshot->heartbeatPath)->toBe('/heartbeat.json')
        ->and($snapshot->emergencyPath)->toBe('/p/01m708vt2qwrtpzk61e80vaby8/emergency.json');
});

it('reads every branch of the contract', function (): void {
    $snapshot = Snapshot::fromArray(fixtureJson('current-incident.json'));
    $major = $snapshot->activeIncidents[1];

    expect($snapshot->version)->toBe(7)
        ->and($snapshot->page->brandColor)->toBe('#0f766e')
        ->and($snapshot->component('cmp-billing')?->status)->toBe(ComponentStatus::UnderMaintenance)
        ->and($snapshot->component('nope'))->toBeNull()
        ->and($major->impact)->toBe(IncidentImpact::Major)
        ->and($major->state)->toBe(IncidentState::Identified)
        ->and($major->latestUpdate()?->body)->toBe('A database failover is in progress.')
        ->and($major->componentIds())->toBe(['cmp-api'])
        ->and($snapshot->recentIncidents[0]->href)->toBe('/p/01pageid/incidents/inc-old.json')
        ->and($snapshot->recentIncidents[0]->resolvedAt?->toIso8601ZuluString())->toBe('2026-11-08T11:00:00Z')
        ->and($snapshot->activeMaintenances[0]->componentIds)->toBe(['cmp-billing'])
        ->and($snapshot->upcomingMaintenances)->toHaveCount(2)
        ->and($snapshot->fallbackOrigins)->toBe(['https://data.cboxstatus.com']);
});

it('writes back exactly what it read, in contract shape', function (string $name): void {
    $array = Snapshot::fromArray(fixtureJson($name))->toArray();

    assertMatchesContract($array);
    expect($array)->toEqual(fixtureJson($name));
})->with(['current-live.json', 'current-incident.json']);

it('ignores keys it does not know, because v1 only grows', function (): void {
    $current = fixtureJson('current-live.json');
    $current['new_section'] = ['anything' => true];
    $current['page']['logo'] = 'https://example.test/logo.svg';

    expect(Snapshot::fromArray($current)->page->name)->toBe('Cbox');
});

it('refuses a document missing anything the contract requires', function (string $path): void {
    $current = fixtureJson('current-incident.json');
    $keys = explode('.', $path);
    $last = array_pop($keys);
    $node = &$current;

    foreach ($keys as $key) {
        $node = &$node[is_numeric($key) ? (int) $key : $key];
    }

    unset($node[$last]);

    expect(fn () => Snapshot::fromArray($current))->toThrow(InvalidSnapshot::class);
})->with(function (): array {
    $schema = fixtureJson('current.v1.schema.json');
    $required = $schema['required'];
    $paths = is_array($required) ? array_filter($required, fn (mixed $key): bool => $key !== 'schema') : [];

    return [
        ...array_values($paths),
        'page.id', 'page.name', 'page.url', 'page.timezone',
        'status.indicator', 'status.description',
        'groups.0.components', 'components.0.status', 'components.0.name',
        'incidents.active', 'incidents.recent', 'incidents.active.0.updates', 'incidents.active.0.updates.0.created_at',
        'incidents.recent.0.started_at', 'maintenances.active', 'maintenances.upcoming',
        'maintenances.active.0.scheduled_until', 'links.heartbeat', 'links.emergency',
    ];
});

it('refuses another schema, a status that is not a string and a malformed timestamp', function (array $change): void {
    $current = array_replace_recursive(fixtureJson('current-live.json'), $change);

    expect(fn () => Snapshot::fromArray($current))->toThrow(InvalidSnapshot::class);
})->with([
    'schema v2' => [['schema' => 'cbox-status/v2']],
    'indicator not a string' => [['status' => ['indicator' => 3]]],
    'not RFC 3339' => [['generated_at' => '10/11/2026']],
    'zero stale_after_seconds' => [['stale_after_seconds' => 0]],
]);

it('reads values added to the contract after this client, keeping the raw strings', function (): void {
    $snapshot = Snapshot::fromArray(fixtureJson('current-unknown.json'));
    $incident = $snapshot->activeIncidents[1];
    $recent = $snapshot->recentIncidents[0];

    expect($snapshot->status->indicator)->toBe(ComponentStatus::Unknown)
        ->and($snapshot->status->indicatorValue)->toBe('read_only')
        ->and($snapshot->component('cmp-api')?->status)->toBe(ComponentStatus::Unknown)
        ->and($snapshot->component('cmp-signin')?->statusValue)->toBe('rate_limited')
        ->and($snapshot->component('cmp-docs')?->statusValue)->toBe('operational')
        ->and($incident->state)->toBe(IncidentState::Unknown)
        ->and($incident->stateValue)->toBe('postmortem')
        ->and($incident->impact)->toBe(IncidentImpact::Unknown)
        ->and($incident->impactValue)->toBe('severe')
        ->and($incident->updates[0]->stateValue)->toBe('postmortem')
        ->and($incident->updates[0]->components[0]->status)->toBe(ComponentStatus::Unknown)
        ->and($recent->stateValue)->toBe('archived')
        ->and($recent->impact)->toBe(IncidentImpact::Unknown);

    // Written back as it was read: the raw strings survive, not 'unknown'.
    expect($snapshot->toArray())->toEqual(fixtureJson('current-unknown.json'));
});

it('never ranks an unknown status as operational', function (): void {
    expect(ComponentStatus::Unknown->severity())->toBeGreaterThan(ComponentStatus::Operational->severity())
        ->and(IncidentImpact::Unknown->severity())->toBe(IncidentImpact::Minor->severity());
});
