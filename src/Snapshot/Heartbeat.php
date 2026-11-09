<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Support\Field;

/**
 * `/heartbeat.json`: when the control plane last proved it is alive. Its age, not
 * `generated_at`, decides whether a page is stale: a page with nothing to report is
 * not republished, but the heartbeat keeps ticking.
 */
readonly class Heartbeat
{
    public function __construct(public CarbonImmutable $writtenAt) {}

    /**
     * Null for anything that is not a `cbox-status/v1` heartbeat.
     *
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        if (($data['schema'] ?? null) !== Snapshot::SCHEMA) {
            return null;
        }

        $writtenAt = Field::parseTime($data['written_at'] ?? null);

        return $writtenAt === null ? null : new self($writtenAt);
    }

    /**
     * @return array{schema: string, written_at: string}
     */
    public function toArray(): array
    {
        return ['schema' => Snapshot::SCHEMA, 'written_at' => $this->writtenAt->toIso8601ZuluString()];
    }
}
