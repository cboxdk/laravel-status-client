<?php

declare(strict_types=1);

namespace Cbox\StatusClient;

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Banners\Banner;
use Cbox\StatusClient\Contracts\Banners;
use Cbox\StatusClient\Contracts\StatusReader;

/**
 * The one thing an app talks to: the report, its banners, and the same as an array to
 * share with Inertia. Behind the `Status` facade.
 */
class StatusClient
{
    public function __construct(
        private readonly StatusReader $reader,
        private readonly Banners $banners,
    ) {}

    public function report(): StatusReport
    {
        return $this->reader->read();
    }

    public function refresh(): StatusReport
    {
        return $this->reader->refresh();
    }

    /**
     * @return list<Banner>
     */
    public function banners(?StatusReport $report = null): array
    {
        return $this->banners->for($report ?? $this->report(), CarbonImmutable::now());
    }

    /**
     * For `Inertia::share('status', fn () => Status::shared())`. Plain values only;
     * every string is text, never HTML.
     *
     * @return array{available: bool, stale: bool, indicator: ?string, description: ?string, page: ?array{name: string, url: string}, emergency: ?array{page_id: string, message: string, posted_at: string, expires_at: ?string}, banners: list<array{kind: string, level: string, role: string, id: string, title: string, body: ?string, label: ?string, url: ?string, starts_at: ?string, ends_at: ?string}>}
     */
    public function shared(): array
    {
        $report = $this->report();
        $page = $report->snapshot?->page;

        return [
            'available' => $report->available(),
            'stale' => $report->stale,
            'indicator' => $report->indicator()?->value,
            'description' => $report->snapshot?->status->description,
            'page' => $page === null ? null : ['name' => $page->name, 'url' => $page->url],
            'emergency' => $report->emergency?->toArray(),
            'banners' => array_map(fn (Banner $banner): array => $banner->toArray(), $this->banners($report)),
        ];
    }
}
