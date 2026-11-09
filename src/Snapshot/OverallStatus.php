<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Cbox\StatusClient\Support\Field;

/**
 * The `status` object: the page-wide indicator and its one-line description.
 */
readonly class OverallStatus
{
    public function __construct(
        public ComponentStatus $indicator = ComponentStatus::Operational,
        public string $description = 'All systems operational',
    ) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path = 'status'): self
    {
        return new self(
            Field::enum($data, 'indicator', ComponentStatus::class, $path),
            Field::string($data, 'description', $path),
        );
    }

    /**
     * @return array{indicator: string, description: string}
     */
    public function toArray(): array
    {
        return ['indicator' => $this->indicator->value, 'description' => $this->description];
    }
}
