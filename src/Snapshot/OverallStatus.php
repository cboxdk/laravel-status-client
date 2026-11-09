<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Cbox\StatusClient\Support\Field;

/**
 * The `status` object: the page-wide indicator and its one-line description.
 */
readonly class OverallStatus
{
    /** The raw string from the JSON; differs from `$indicator->value` only for Unknown. */
    public string $indicatorValue;

    public function __construct(
        public ComponentStatus $indicator = ComponentStatus::Operational,
        public string $description = 'All systems operational',
        ?string $indicatorValue = null,
    ) {
        $this->indicatorValue = $indicatorValue ?? $indicator->value;
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path = 'status'): self
    {
        return new self(
            Field::enum($data, 'indicator', ComponentStatus::class, ComponentStatus::Unknown, $path),
            Field::string($data, 'description', $path),
            Field::string($data, 'indicator', $path),
        );
    }

    /**
     * @return array{indicator: string, description: string}
     */
    public function toArray(): array
    {
        return ['indicator' => $this->indicatorValue, 'description' => $this->description];
    }
}
