<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Cbox\StatusClient\Support\Field;

/**
 * One component on the page and its current status. `$statusValue` is the raw string
 * from the JSON, which differs from `$status->value` only when the status is Unknown.
 */
readonly class Component
{
    public string $statusValue;

    public function __construct(
        public string $id,
        public string $name,
        public ComponentStatus $status = ComponentStatus::Operational,
        public ?string $groupId = null,
        public ?string $description = null,
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
            id: Field::string($data, 'id', $path),
            name: Field::string($data, 'name', $path),
            status: Field::enum($data, 'status', ComponentStatus::class, ComponentStatus::Unknown, $path),
            groupId: Field::nullableString($data, 'group_id', $path),
            description: Field::nullableString($data, 'description', $path),
            statusValue: Field::string($data, 'status', $path),
        );
    }

    /**
     * @return array{id: string, name: string, description: ?string, group_id: ?string, status: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'group_id' => $this->groupId,
            'status' => $this->statusValue,
        ];
    }
}
