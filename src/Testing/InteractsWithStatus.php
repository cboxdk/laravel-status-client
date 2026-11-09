<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Testing;

use Cbox\StatusClient\Contracts\StatusReader;
use Illuminate\Contracts\Foundation\Application;
use LogicException;

/**
 * Swap the status page for {@see FakeStatus}. The facade, the Blade component, the
 * Inertia array and `status:check` all read the fake from then on.
 */
trait InteractsWithStatus
{
    protected function fakeStatus(?FakeStatus $fake = null): FakeStatus
    {
        $fake ??= new FakeStatus;

        $this->statusApplication()->instance(StatusReader::class, $fake);

        return $fake;
    }

    private function statusApplication(): Application
    {
        $app = $this->app ?? null;

        if (! $app instanceof Application) {
            throw new LogicException('fakeStatus() needs a booted application; use it from a Laravel or Testbench test case.');
        }

        return $app;
    }
}
