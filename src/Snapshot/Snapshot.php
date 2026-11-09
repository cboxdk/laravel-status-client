<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Exceptions\InvalidSnapshot;
use Cbox\StatusClient\Support\Field;

/**
 * `current.json`, contract `cbox-status/v1`, as typed values. Parsing is strict on
 * what the contract requires and ignores keys it does not know, because v1 only grows.
 * `new Snapshot` is a valid, empty, operational page, so a test can build one by hand.
 */
readonly class Snapshot
{
    public const string SCHEMA = 'cbox-status/v1';

    /**
     * @param  list<Group>  $groups
     * @param  list<Component>  $components
     * @param  list<Incident>  $activeIncidents
     * @param  list<IncidentSummary>  $recentIncidents
     * @param  list<Maintenance>  $activeMaintenances
     * @param  list<Maintenance>  $upcomingMaintenances
     * @param  list<string>  $fallbackOrigins
     */
    public function __construct(
        public Page $page = new Page,
        public OverallStatus $status = new OverallStatus,
        public ?CarbonImmutable $generatedAt = null,
        public int $staleAfterSeconds = 300,
        public int $version = 1,
        public array $groups = [],
        public array $components = [],
        public array $activeIncidents = [],
        public array $recentIncidents = [],
        public array $activeMaintenances = [],
        public array $upcomingMaintenances = [],
        public string $heartbeatPath = '/heartbeat.json',
        public ?string $emergencyPath = null,
        public array $fallbackOrigins = [],
    ) {}

    /**
     * @param  array<mixed>  $data
     *
     * @throws InvalidSnapshot when the document is not a valid `cbox-status/v1` snapshot
     */
    public static function fromArray(array $data): self
    {
        if (($data['schema'] ?? null) !== self::SCHEMA) {
            throw InvalidSnapshot::field('schema', '"'.self::SCHEMA.'"');
        }

        $incidents = Field::object($data, 'incidents', '$');
        $maintenances = Field::object($data, 'maintenances', '$');
        $links = Field::object($data, 'links', '$');

        return new self(
            page: Page::fromArray(Field::object($data, 'page', '$'), '$.page'),
            status: OverallStatus::fromArray(Field::object($data, 'status', '$'), '$.status'),
            generatedAt: Field::time($data, 'generated_at', '$'),
            staleAfterSeconds: Field::positiveInt($data, 'stale_after_seconds', '$'),
            version: Field::positiveInt($data, 'version', '$'),
            groups: self::each($data, 'groups', '$', Group::fromArray(...)),
            components: self::each($data, 'components', '$', Component::fromArray(...)),
            activeIncidents: self::each($incidents, 'active', '$.incidents', Incident::fromArray(...)),
            recentIncidents: self::each($incidents, 'recent', '$.incidents', IncidentSummary::fromArray(...)),
            activeMaintenances: self::each($maintenances, 'active', '$.maintenances', Maintenance::fromArray(...)),
            upcomingMaintenances: self::each($maintenances, 'upcoming', '$.maintenances', Maintenance::fromArray(...)),
            heartbeatPath: Field::string($links, 'heartbeat', '$.links'),
            emergencyPath: Field::string($links, 'emergency', '$.links'),
            fallbackOrigins: Field::strings($data, 'fallback_origins', '$'),
        );
    }

    public function component(string $id): ?Component
    {
        foreach ($this->components as $component) {
            if ($component->id === $id) {
                return $component;
            }
        }

        return null;
    }

    /**
     * The snapshot in contract shape, for an Inertia prop or a JSON response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema' => self::SCHEMA,
            'version' => $this->version,
            'generated_at' => $this->generatedAt?->toIso8601ZuluString(),
            'stale_after_seconds' => $this->staleAfterSeconds,
            'page' => $this->page->toArray(),
            'status' => $this->status->toArray(),
            'groups' => self::arrays($this->groups),
            'components' => self::arrays($this->components),
            'incidents' => ['active' => self::arrays($this->activeIncidents), 'recent' => self::arrays($this->recentIncidents)],
            'maintenances' => ['active' => self::arrays($this->activeMaintenances), 'upcoming' => self::arrays($this->upcomingMaintenances)],
            'links' => ['heartbeat' => $this->heartbeatPath, 'emergency' => $this->emergencyPath ?? '/p/'.$this->page->id.'/emergency.json'],
            'fallback_origins' => $this->fallbackOrigins,
        ];
    }

    /**
     * @param  list<Group|Component|Incident|IncidentSummary|Maintenance>  $items
     * @return list<array<string, mixed>>
     */
    private static function arrays(array $items): array
    {
        $arrays = [];

        foreach ($items as $item) {
            $arrays[] = $item->toArray();
        }

        return $arrays;
    }

    /**
     * @template T
     *
     * @param  array<mixed>  $data
     * @param  callable(array<mixed>, string): T  $make
     * @return list<T>
     */
    private static function each(array $data, string $key, string $path, callable $make): array
    {
        $items = [];

        foreach (Field::objects($data, $key, $path) as $index => $item) {
            $items[] = $make($item, sprintf('%s.%s[%d]', $path, $key, $index));
        }

        return $items;
    }
}
