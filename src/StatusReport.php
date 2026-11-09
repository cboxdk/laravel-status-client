<?php

declare(strict_types=1);

namespace Cbox\StatusClient;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Cbox\StatusClient\Emergency\EmergencyNotice;
use Cbox\StatusClient\Snapshot\ComponentStatus;
use Cbox\StatusClient\Snapshot\Incident;
use Cbox\StatusClient\Snapshot\Maintenance;
use Cbox\StatusClient\Snapshot\Snapshot;

/**
 * What a read found: the snapshot (null when no origin answered and nothing was cached),
 * whether it is stale, a verified emergency notice if one is posted, and where it came
 * from. `new StatusReport` is "unavailable", the honest answer when nothing is known.
 */
readonly class StatusReport
{
    public function __construct(
        public ?Snapshot $snapshot = null,
        public bool $stale = true,
        public ?CarbonImmutable $heartbeatAt = null,
        public ?EmergencyNotice $emergency = null,
        public ?string $origin = null,
        public ?CarbonImmutable $fetchedAt = null,
        public bool $fetchFailed = false,
    ) {}

    public static function unavailable(?EmergencyNotice $emergency = null, bool $fetchFailed = true): self
    {
        return new self(emergency: $emergency, fetchFailed: $fetchFailed);
    }

    public function available(): bool
    {
        return $this->snapshot !== null;
    }

    public function indicator(): ?ComponentStatus
    {
        return $this->snapshot?->status->indicator;
    }

    /** Operational, fresh, nothing active, no emergency notice. */
    public function allClear(): bool
    {
        return $this->snapshot !== null
            && ! $this->stale
            && $this->emergency === null
            && $this->snapshot->status->indicator === ComponentStatus::Operational
            && $this->snapshot->activeIncidents === []
            && $this->snapshot->activeMaintenances === [];
    }

    /** Seconds since the last heartbeat, or null when there was none. */
    public function heartbeatAge(?CarbonInterface $now = null): ?int
    {
        if ($this->heartbeatAt === null) {
            return null;
        }

        return max(0, (int) floor($this->heartbeatAt->diffInSeconds($now ?? CarbonImmutable::now(), false)));
    }

    /**
     * @return list<Incident>
     */
    public function activeIncidents(): array
    {
        return $this->snapshot->activeIncidents ?? [];
    }

    /**
     * @return list<Maintenance>
     */
    public function activeMaintenances(): array
    {
        return $this->snapshot->activeMaintenances ?? [];
    }

    /**
     * @return list<Maintenance>
     */
    public function upcomingMaintenances(): array
    {
        return $this->snapshot->upcomingMaintenances ?? [];
    }
}
