<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

/**
 * `$defs.incidentState` in the contract. `Unknown` stands for a state added after this
 * client was released; the raw string stays on the value object.
 */
enum IncidentState: string
{
    case Investigating = 'investigating';
    case Identified = 'identified';
    case Monitoring = 'monitoring';
    case Resolved = 'resolved';
    case Unknown = 'unknown';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
