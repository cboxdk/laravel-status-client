<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

/**
 * `$defs.componentStatus` in the contract. Also the page-wide `status.indicator`.
 * `Unknown` stands for any value added to the contract after this client was released;
 * the raw string stays on the value object. It is never treated as operational.
 */
enum ComponentStatus: string
{
    case Operational = 'operational';
    case DegradedPerformance = 'degraded_performance';
    case PartialOutage = 'partial_outage';
    case MajorOutage = 'major_outage';
    case UnderMaintenance = 'under_maintenance';
    case Unknown = 'unknown';

    /** 0 for operational, higher is worse; maintenance and unknown rank with degraded performance. */
    public function severity(): int
    {
        return match ($this) {
            self::Operational => 0,
            self::UnderMaintenance, self::DegradedPerformance, self::Unknown => 1,
            self::PartialOutage => 2,
            self::MajorOutage => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operational',
            self::DegradedPerformance => 'Degraded performance',
            self::PartialOutage => 'Partial outage',
            self::MajorOutage => 'Major outage',
            self::UnderMaintenance => 'Under maintenance',
            self::Unknown => 'Unknown',
        };
    }
}
