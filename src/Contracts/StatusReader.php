<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Contracts;

use Cbox\StatusClient\StatusReport;

/**
 * Reads a status page's public JSON. Never throws: an unreachable page is a report
 * that says so, because a status banner must not take the host app down with it.
 */
interface StatusReader
{
    /** The cached report while it is younger than the TTL, otherwise {@see refresh()}. */
    public function read(): StatusReport;

    /**
     * Fetch now, from the primary origin and then each fallback. When none answers, the
     * last known report is served instead (stale-if-error), marked `fetchFailed`.
     */
    public function refresh(): StatusReport;
}
