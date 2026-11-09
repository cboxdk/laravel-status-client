<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Support\Field;

/**
 * An active incident with every update posted so far (`incidents.active`). Unlike a
 * recent incident it has no `href`: its updates are already here.
 */
readonly class Incident
{
    /**
     * @param  list<IncidentUpdate>  $updates
     */
    public function __construct(
        public string $id,
        public string $title,
        public IncidentState $state,
        public IncidentImpact $impact,
        public CarbonImmutable $startedAt,
        public ?CarbonImmutable $resolvedAt = null,
        public array $updates = [],
    ) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        $updates = [];

        foreach (Field::objects($data, 'updates', $path) as $index => $update) {
            $updates[] = IncidentUpdate::fromArray($update, sprintf('%s.updates[%d]', $path, $index));
        }

        return new self(
            id: Field::string($data, 'id', $path),
            title: Field::string($data, 'title', $path),
            state: Field::enum($data, 'state', IncidentState::class, $path),
            impact: Field::enum($data, 'impact', IncidentImpact::class, $path),
            startedAt: Field::time($data, 'started_at', $path),
            resolvedAt: Field::nullableTime($data, 'resolved_at', $path),
            updates: $updates,
        );
    }

    /** The newest update by `created_at`, whatever order the file lists them in. */
    public function latestUpdate(): ?IncidentUpdate
    {
        $latest = null;

        foreach ($this->updates as $update) {
            if ($latest === null || $update->createdAt->greaterThan($latest->createdAt)) {
                $latest = $update;
            }
        }

        return $latest;
    }

    /**
     * Every component id any update named.
     *
     * @return list<string>
     */
    public function componentIds(): array
    {
        $ids = [];

        foreach ($this->updates as $update) {
            foreach ($update->components as $component) {
                $ids[$component->id] = true;
            }
        }

        return array_keys($ids);
    }

    /**
     * @return array{id: string, title: string, state: string, impact: string, started_at: string, resolved_at: ?string, updates: list<array{id: string, state: string, body: string, created_at: string, components: list<array{id: string, status: string}>}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'state' => $this->state->value,
            'impact' => $this->impact->value,
            'started_at' => $this->startedAt->toIso8601ZuluString(),
            'resolved_at' => $this->resolvedAt?->toIso8601ZuluString(),
            'updates' => array_map(fn (IncidentUpdate $update): array => $update->toArray(), $this->updates),
        ];
    }
}
