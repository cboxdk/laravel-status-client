<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

/**
 * An incident's `impact` in the contract. `Unknown` stands for an impact added after this
 * client was released; it ranks with minor, so it is shown but never as the worst case.
 */
enum IncidentImpact: string
{
    case None = 'none';
    case Minor = 'minor';
    case Major = 'major';
    case Critical = 'critical';
    case Unknown = 'unknown';

    public function severity(): int
    {
        return match ($this) {
            self::None => 0,
            self::Minor, self::Unknown => 1,
            self::Major => 2,
            self::Critical => 3,
        };
    }
}
