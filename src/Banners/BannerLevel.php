<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Banners;

enum BannerLevel: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Danger = 'danger';

    /** `alert` interrupts a screen reader; keep it for what people must hear now. */
    public function role(): string
    {
        return $this === self::Danger ? 'alert' : 'status';
    }
}
