<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Http;

use Cbox\StatusClient\Contracts\StatusReader;
use Cbox\StatusClient\StatusReport;

/**
 * Bound when no page id or URL is configured: every read is "unavailable", nothing is
 * fetched, no banner is shown.
 */
class NullStatusReader implements StatusReader
{
    public function read(): StatusReport
    {
        return StatusReport::unavailable(fetchFailed: false);
    }

    public function refresh(): StatusReport
    {
        return $this->read();
    }
}
