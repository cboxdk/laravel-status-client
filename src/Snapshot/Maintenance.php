<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Support\Field;

/**
 * A scheduled maintenance window, active or upcoming.
 */
readonly class Maintenance
{
    /**
     * @param  list<string>  $componentIds
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $body,
        public CarbonImmutable $scheduledFor,
        public CarbonImmutable $scheduledUntil,
        public array $componentIds = [],
    ) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        return new self(
            Field::string($data, 'id', $path),
            Field::string($data, 'title', $path),
            Field::string($data, 'body', $path),
            Field::time($data, 'scheduled_for', $path),
            Field::time($data, 'scheduled_until', $path),
            Field::strings($data, 'components', $path),
        );
    }

    /**
     * @return array{id: string, title: string, body: string, scheduled_for: string, scheduled_until: string, components: list<string>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'scheduled_for' => $this->scheduledFor->toIso8601ZuluString(),
            'scheduled_until' => $this->scheduledUntil->toIso8601ZuluString(),
            'components' => $this->componentIds,
        ];
    }
}
