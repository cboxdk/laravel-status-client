<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Testing;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Cbox\StatusClient\Contracts\StatusReader;
use Cbox\StatusClient\Emergency\EmergencyNotice;
use Cbox\StatusClient\Snapshot\Component;
use Cbox\StatusClient\Snapshot\ComponentImpact;
use Cbox\StatusClient\Snapshot\ComponentStatus;
use Cbox\StatusClient\Snapshot\Incident;
use Cbox\StatusClient\Snapshot\IncidentImpact;
use Cbox\StatusClient\Snapshot\IncidentState;
use Cbox\StatusClient\Snapshot\IncidentUpdate;
use Cbox\StatusClient\Snapshot\Maintenance;
use Cbox\StatusClient\Snapshot\OverallStatus;
use Cbox\StatusClient\Snapshot\Page;
use Cbox\StatusClient\Snapshot\Snapshot;
use Cbox\StatusClient\StatusReport;
use JsonException;
use PHPUnit\Framework\Assert;

/**
 * A status page in memory. Starts operational and fresh; say what else the test needs.
 * Nothing is fetched, nothing is cached.
 *
 *     $this->fakeStatus()->withIncident('Sign-in is slow', IncidentImpact::Minor);
 */
class FakeStatus implements StatusReader
{
    public const string URL = 'https://status.example.test';

    private Snapshot $snapshot;

    private bool $available = true;

    private bool $stale = false;

    private ?EmergencyNotice $emergency = null;

    private int $reads = 0;

    public function __construct(?Snapshot $snapshot = null)
    {
        $this->snapshot = $snapshot ?? new Snapshot(
            page: new Page('fake-page', 'Example', self::URL),
            generatedAt: CarbonImmutable::now(),
            emergencyPath: '/p/fake-page/emergency.json',
        );
    }

    /**
     * A fake serving a real `current.json`, e.g. one saved from a live page.
     *
     * @throws JsonException
     */
    public static function fromFile(string $path): self
    {
        $data = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);

        return new self(Snapshot::fromArray(is_array($data) ? $data : []));
    }

    public function withSnapshot(Snapshot $snapshot): static
    {
        $this->snapshot = $snapshot;

        return $this;
    }

    public function withIndicator(ComponentStatus $indicator, ?string $description = null): static
    {
        return $this->change(status: new OverallStatus($indicator, $description ?? $indicator->label()));
    }

    public function withComponent(string $id, string $name, ComponentStatus $status = ComponentStatus::Operational): static
    {
        return $this->change(components: [...$this->snapshot->components, new Component($id, $name, $status)]);
    }

    /**
     * @param  list<string>  $components  component ids the incident affects; empty is page-wide
     */
    public function withIncident(
        string $title,
        IncidentImpact $impact = IncidentImpact::Major,
        string $body = 'We are investigating.',
        IncidentState $state = IncidentState::Investigating,
        array $components = [],
        ?string $id = null,
    ): static {
        $id ??= 'incident-'.(count($this->snapshot->activeIncidents) + 1);
        $now = CarbonImmutable::now();
        $impacts = array_map(fn (string $component): ComponentImpact => new ComponentImpact($component, ComponentStatus::PartialOutage), $components);

        return $this->change(activeIncidents: [
            ...$this->snapshot->activeIncidents,
            new Incident($id, $title, $state, $impact, $now, updates: [new IncidentUpdate($id.'-update-1', $state, $body, $now, $impacts)]),
        ]);
    }

    /**
     * Active when the window contains now, upcoming when it starts later.
     *
     * @param  list<string>  $components
     */
    public function withMaintenance(
        string $title,
        CarbonInterface $from,
        CarbonInterface $until,
        string $body = '',
        array $components = [],
        ?string $id = null,
    ): static {
        $count = count($this->snapshot->activeMaintenances) + count($this->snapshot->upcomingMaintenances);
        $maintenance = new Maintenance($id ?? 'maintenance-'.($count + 1), $title, $body, $from->toImmutable()->utc(), $until->toImmutable()->utc(), $components);

        if ($from->greaterThan(CarbonImmutable::now())) {
            return $this->change(upcomingMaintenances: [...$this->snapshot->upcomingMaintenances, $maintenance]);
        }

        return $this->change(activeMaintenances: [...$this->snapshot->activeMaintenances, $maintenance]);
    }

    public function withEmergency(string $message, ?CarbonInterface $expiresAt = null): static
    {
        $this->emergency = new EmergencyNotice($this->snapshot->page->id, $message, CarbonImmutable::now(), $expiresAt?->toImmutable());

        return $this;
    }

    /** The heartbeat is an hour old. */
    public function stale(bool $stale = true): static
    {
        $this->stale = $stale;

        return $this;
    }

    /** No origin answered and nothing is cached. An emergency notice still shows. */
    public function unavailable(): static
    {
        $this->available = false;

        return $this;
    }

    public function read(): StatusReport
    {
        $this->reads++;
        $now = CarbonImmutable::now();

        if (! $this->available) {
            return StatusReport::unavailable($this->emergency);
        }

        return new StatusReport(
            snapshot: $this->snapshot,
            stale: $this->stale,
            heartbeatAt: $this->stale ? $now->subHour() : $now,
            emergency: $this->emergency,
            origin: self::URL,
            fetchedAt: $now,
        );
    }

    public function refresh(): StatusReport
    {
        return $this->read();
    }

    public function reads(): int
    {
        return $this->reads;
    }

    public function assertRead(?int $times = null): void
    {
        if ($times === null) {
            Assert::assertGreaterThan(0, $this->reads, 'The status page was never read.');

            return;
        }

        Assert::assertSame($times, $this->reads, sprintf('The status page was read %d times, expected %d.', $this->reads, $times));
    }

    public function assertNotRead(): void
    {
        Assert::assertSame(0, $this->reads, sprintf('The status page was read %d times, expected none.', $this->reads));
    }

    /**
     * @param  list<Component>|null  $components
     * @param  list<Incident>|null  $activeIncidents
     * @param  list<Maintenance>|null  $activeMaintenances
     * @param  list<Maintenance>|null  $upcomingMaintenances
     */
    private function change(
        ?OverallStatus $status = null,
        ?array $components = null,
        ?array $activeIncidents = null,
        ?array $activeMaintenances = null,
        ?array $upcomingMaintenances = null,
    ): static {
        $s = $this->snapshot;

        $this->snapshot = new Snapshot(
            page: $s->page,
            status: $status ?? $s->status,
            generatedAt: $s->generatedAt,
            staleAfterSeconds: $s->staleAfterSeconds,
            version: $s->version,
            groups: $s->groups,
            components: $components ?? $s->components,
            activeIncidents: $activeIncidents ?? $s->activeIncidents,
            recentIncidents: $s->recentIncidents,
            activeMaintenances: $activeMaintenances ?? $s->activeMaintenances,
            upcomingMaintenances: $upcomingMaintenances ?? $s->upcomingMaintenances,
            heartbeatPath: $s->heartbeatPath,
            emergencyPath: $s->emergencyPath,
            fallbackOrigins: $s->fallbackOrigins,
        );

        return $this;
    }
}
