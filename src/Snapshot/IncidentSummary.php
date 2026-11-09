<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Support\Field;

/**
 * A recent incident without its updates (`incidents.recent`). `href` is the path of
 * the incident's own JSON file on the same origin, when the page published one.
 */
readonly class IncidentSummary
{
    public function __construct(
        public string $id,
        public string $title,
        public IncidentState $state,
        public IncidentImpact $impact,
        public CarbonImmutable $startedAt,
        public ?CarbonImmutable $resolvedAt = null,
        public ?string $href = null,
    ) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        return new self(
            id: Field::string($data, 'id', $path),
            title: Field::string($data, 'title', $path),
            state: Field::enum($data, 'state', IncidentState::class, $path),
            impact: Field::enum($data, 'impact', IncidentImpact::class, $path),
            startedAt: Field::time($data, 'started_at', $path),
            resolvedAt: Field::nullableTime($data, 'resolved_at', $path),
            href: Field::nullableString($data, 'href', $path),
        );
    }

    /**
     * @return array{id: string, title: string, state: string, impact: string, started_at: string, resolved_at: ?string, href: ?string}
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
            'href' => $this->href,
        ];
    }
}
