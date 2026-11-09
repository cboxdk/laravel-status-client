<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Cbox\StatusClient\Support\Field;

/**
 * A component named by an incident update, and the status the update gave it.
 */
readonly class ComponentImpact
{
    /** The raw string from the JSON; differs from `$status->value` only for Unknown. */
    public string $statusValue;

    public function __construct(
        public string $id,
        public ComponentStatus $status,
        ?string $statusValue = null,
    ) {
        $this->statusValue = $statusValue ?? $status->value;
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        return new self(
            Field::string($data, 'id', $path),
            Field::enum($data, 'status', ComponentStatus::class, ComponentStatus::Unknown, $path),
            Field::string($data, 'status', $path),
        );
    }

    /**
     * @return array{id: string, status: string}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'status' => $this->statusValue];
    }
}
