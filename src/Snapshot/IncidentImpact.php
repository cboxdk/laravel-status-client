<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

/**
 * An incident's `impact` in the contract.
 */
enum IncidentImpact: string
{
    case None = 'none';
    case Minor = 'minor';
    case Major = 'major';
    case Critical = 'critical';

    public function severity(): int
    {
        return match ($this) {
            self::None => 0,
            self::Minor => 1,
            self::Major => 2,
            self::Critical => 3,
        };
    }
}
