<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Console;

use Cbox\StatusClient\Snapshot\ComponentStatus;
use Cbox\StatusClient\StatusClient;
use Cbox\StatusClient\StatusConfig;
use Illuminate\Console\Command;

/**
 * Fetch the page now (or read the cache with --cached) and say what it shows. Exits 0
 * when the page answered and is fresh, 1 otherwise, so it works as a probe. Scheduled
 * every minute it also keeps the cache warm, so no web request waits on the fetch.
 */
class StatusCheckCommand extends Command
{
    protected $signature = 'status:check
        {--cached : Read through the cache instead of fetching now}
        {--json : Print the report as JSON}';

    protected $description = 'Read the configured Cbox Status page and report what it shows';

    public function handle(StatusClient $status, StatusConfig $config): int
    {
        if (! $config->configured()) {
            $this->error('Cbox Status is not configured: set CBOX_STATUS_PAGE and CBOX_STATUS_URL.');

            return self::FAILURE;
        }

        $report = $this->option('cached') === true ? $status->report() : $status->refresh();
        $banners = $status->banners($report);
        $healthy = $report->available() && ! $report->stale;

        if ($this->option('json') === true) {
            $this->line((string) json_encode([
                'healthy' => $healthy,
                'origin' => $report->origin,
                'heartbeat_age' => $report->heartbeatAge(),
                'fetch_failed' => $report->fetchFailed,
                ...$status->shared(),
                'banners' => array_map(fn ($banner): array => $banner->toArray(), $banners),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $healthy ? self::SUCCESS : self::FAILURE;
        }

        $snapshot = $report->snapshot;

        if ($snapshot === null) {
            $this->error('No origin answered with page '.$config->pageId.' and nothing is cached.');
        } else {
            $this->line(sprintf('<info>%s</info> (%s)', $snapshot->page->name, $snapshot->page->url));
            $indicator = $snapshot->status->indicator === ComponentStatus::Unknown
                ? 'Unknown ('.$snapshot->status->indicatorValue.')'
                : $snapshot->status->indicator->label();
            $this->line(sprintf('Status:    %s, %s', $indicator, $snapshot->status->description));
            $this->line('Origin:    '.($report->origin ?? 'unknown').($report->fetchFailed ? ' (last known, every origin failed)' : ''));
            $age = $report->heartbeatAge();
            $this->line(sprintf('Heartbeat: %s, %s', $age === null ? 'none' : $age.'s ago', $report->stale ? '<comment>stale</comment>' : 'fresh'));
        }

        if ($banners === []) {
            $this->line('Banners:   none');
        }

        foreach ($banners as $banner) {
            $this->line(sprintf('Banner:    [%s] %s%s', $banner->level->value, $banner->title, $banner->label === null ? '' : ' ('.$banner->label.')'));
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
