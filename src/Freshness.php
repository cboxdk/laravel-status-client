<?php

declare(strict_types=1);

namespace Cbox\StatusClient;

use Carbon\CarbonInterface;
use Cbox\StatusClient\Snapshot\Heartbeat;

/**
 * A page is stale when the control plane has not written a heartbeat for longer than
 * the snapshot's `stale_after_seconds`, or when there is no heartbeat at all. The same
 * rule as the page shell's `isStale()`: exactly at the limit is still fresh.
 */
class Freshness
{
    public static function isStale(?Heartbeat $heartbeat, int $staleAfterSeconds, CarbonInterface $now): bool
    {
        if ($heartbeat === null) {
            return true;
        }

        return $heartbeat->writtenAt->diffInMilliseconds($now, false) > $staleAfterSeconds * 1000;
    }
}
