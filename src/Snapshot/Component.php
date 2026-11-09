<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Cbox\StatusClient\Support\Field;

/**
 * One component on the page and its current status.
 */
readonly class Component
{
    public function __construct(
        public string $id,
        public string $name,
        public ComponentStatus $status = ComponentStatus::Operational,
        public ?string $groupId = null,
        public ?string $description = null,
    ) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        return new self(
            id: Field::string($data, 'id', $path),
            name: Field::string($data, 'name', $path),
            status: Field::enum($data, 'status', ComponentStatus::class, $path),
            groupId: Field::nullableString($data, 'group_id', $path),
            description: Field::nullableString($data, 'description', $path),
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
            'status' => $this->status->value,
        ];
    }
}
