<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

/**
 * `$defs.incidentState` in the contract.
 */
enum IncidentState: string
{
    case Investigating = 'investigating';
    case Identified = 'identified';
    case Monitoring = 'monitoring';
    case Resolved = 'resolved';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
