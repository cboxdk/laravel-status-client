<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Facades;

use Cbox\StatusClient\Banners\Banner;
use Cbox\StatusClient\StatusClient;
use Cbox\StatusClient\StatusReport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static StatusReport report()
 * @method static StatusReport refresh()
 * @method static list<Banner> banners(?StatusReport $report = null)
 * @method static array<string, mixed> shared()
 *
 * @see StatusClient
 */
class Status extends Facade
{
    /** Resolved on every call, so a fake bound mid-test is used straight away. */
    protected static $cached = false;

    protected static function getFacadeAccessor(): string
    {
        return StatusClient::class;
    }
}
