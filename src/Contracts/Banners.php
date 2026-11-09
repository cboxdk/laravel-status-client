<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Contracts;

use Carbon\CarbonInterface;
use Cbox\StatusClient\Banners\Banner;
use Cbox\StatusClient\StatusReport;

/**
 * Turns a report into the banners an app shows. Bind your own to change what is shown
 * or how it is worded; the Blade component and the Inertia array both go through it.
 */
interface Banners
{
    /**
     * Most urgent first.
     *
     * @return list<Banner>
     */
    public function for(StatusReport $report, CarbonInterface $now): array;
}
