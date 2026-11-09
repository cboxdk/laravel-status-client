<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Cbox\StatusClient\Support\Field;

/**
 * A component group: a name and the ids of its components, in display order.
 */
readonly class Group
{
    /**
     * @param  list<string>  $componentIds
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $componentIds = [],
    ) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        return new self(
            Field::string($data, 'id', $path),
            Field::string($data, 'name', $path),
            Field::strings($data, 'components', $path),
        );
    }

    /**
     * @return array{id: string, name: string, components: list<string>}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'components' => $this->componentIds];
    }
}
