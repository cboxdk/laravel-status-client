<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Support\Field;

/**
 * One posted update on an incident.
 */
readonly class IncidentUpdate
{
    /**
     * @param  list<ComponentImpact>  $components
     */
    public function __construct(
        public string $id,
        public IncidentState $state,
        public string $body,
        public CarbonImmutable $createdAt,
        public array $components = [],
    ) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        $components = [];

        foreach (Field::objects($data, 'components', $path) as $index => $component) {
            $components[] = ComponentImpact::fromArray($component, sprintf('%s.components[%d]', $path, $index));
        }

        return new self(
            Field::string($data, 'id', $path),
            Field::enum($data, 'state', IncidentState::class, $path),
            Field::string($data, 'body', $path),
            Field::time($data, 'created_at', $path),
            $components,
        );
    }

    /**
     * @return array{id: string, state: string, body: string, created_at: string, components: list<array{id: string, status: string}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'state' => $this->state->value,
            'body' => $this->body,
            'created_at' => $this->createdAt->toIso8601ZuluString(),
            'components' => array_map(fn (ComponentImpact $impact): array => $impact->toArray(), $this->components),
        ];
    }
}
