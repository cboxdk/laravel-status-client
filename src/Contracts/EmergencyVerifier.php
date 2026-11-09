<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Contracts;

use Carbon\CarbonInterface;
use Cbox\StatusClient\Emergency\EmergencyEnvelope;
use Cbox\StatusClient\Emergency\EmergencyNotice;

/**
 * Decides whether an emergency notice is genuine. A notice is shown only when it is
 * signed by a trusted key, names this page, and has not expired; anything else is null.
 * A compromised mirror can delete a notice but cannot forge one.
 */
interface EmergencyVerifier
{
    public function verify(EmergencyEnvelope $envelope, string $pageId, CarbonInterface $now): ?EmergencyNotice;
}
