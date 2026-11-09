<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Banners;

enum BannerKind: string
{
    /** A signed notice posted with the offline CLI while the control plane was down. */
    case Emergency = 'emergency';

    case Incident = 'incident';

    /** A maintenance window in progress. */
    case Maintenance = 'maintenance';

    /** A maintenance window that starts soon. */
    case UpcomingMaintenance = 'upcoming_maintenance';

    /** The page has not had a heartbeat for longer than it promises; what it shows may be old. */
    case Delayed = 'delayed';
}
