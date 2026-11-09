<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Banners;

use Carbon\CarbonInterface;
use Cbox\StatusClient\Contracts\Banners;
use Cbox\StatusClient\Snapshot\Incident;
use Cbox\StatusClient\Snapshot\IncidentImpact;
use Cbox\StatusClient\Snapshot\IncidentState;
use Cbox\StatusClient\Snapshot\Maintenance;
use Cbox\StatusClient\StatusReport;

/**
 * The emergency notice, then active incidents (worst impact first), maintenance in
 * progress, maintenance starting within `upcoming_within_hours`, and, only when
 * `show_delayed` is on, a "may be delayed" line for a stale page.
 *
 * With a `components` filter, an incident shows when any of its updates names one of
 * those components, and a maintenance when it lists one. An incident or maintenance that
 * names no component at all is page-wide and always shows.
 */
class DefaultBanners implements Banners
{
    /**
     * @param  list<string>  $components
     */
    public function __construct(
        private readonly array $components = [],
        private readonly int $upcomingWithinHours = 48,
        private readonly bool $showDelayed = false,
    ) {}

    public function for(StatusReport $report, CarbonInterface $now): array
    {
        $snapshot = $report->snapshot;
        $url = $this->safeUrl($snapshot?->page->url);
        $banners = [];

        if ($report->emergency !== null) {
            $banners[] = new Banner(
                BannerKind::Emergency,
                BannerLevel::Danger,
                'emergency',
                'Important notice',
                $report->emergency->message,
                'Notice',
                $url,
                $report->emergency->postedAt,
                $report->emergency->expiresAt,
            );
        }

        if ($snapshot === null) {
            return $banners;
        }

        $incidents = array_filter($snapshot->activeIncidents, fn (Incident $incident): bool => $this->relevant($incident->componentIds()));
        usort($incidents, fn (Incident $a, Incident $b): int => [$b->impact->severity(), $b->startedAt] <=> [$a->impact->severity(), $a->startedAt]);

        foreach ($incidents as $incident) {
            $latest = $incident->latestUpdate();

            $banners[] = new Banner(
                BannerKind::Incident,
                match ($incident->impact) {
                    IncidentImpact::Critical, IncidentImpact::Major => BannerLevel::Danger,
                    // A value newer than this client: visible, never alarming, never hidden.
                    IncidentImpact::Minor, IncidentImpact::Unknown => BannerLevel::Warning,
                    IncidentImpact::None => BannerLevel::Info,
                },
                $incident->id,
                $incident->title,
                $latest?->body,
                $latest === null ? $this->stateLabel($incident->state, $incident->stateValue) : $this->stateLabel($latest->state, $latest->stateValue),
                $url,
                $incident->startedAt,
            );
        }

        foreach ($snapshot->activeMaintenances as $maintenance) {
            if ($this->relevant($maintenance->componentIds)) {
                $banners[] = $this->maintenance($maintenance, BannerKind::Maintenance, BannerLevel::Warning, 'In progress', $url);
            }
        }

        $horizon = $now->toImmutable()->addHours($this->upcomingWithinHours);

        foreach ($snapshot->upcomingMaintenances as $maintenance) {
            if ($this->relevant($maintenance->componentIds) && $maintenance->scheduledFor->lessThanOrEqualTo($horizon) && $maintenance->scheduledUntil->greaterThan($now)) {
                $banners[] = $this->maintenance($maintenance, BannerKind::UpcomingMaintenance, BannerLevel::Info, 'Scheduled', $url);
            }
        }

        if ($this->showDelayed && $report->stale) {
            $banners[] = new Banner(
                BannerKind::Delayed,
                BannerLevel::Info,
                'delayed',
                'Status updates may be delayed',
                $report->heartbeatAt === null ? null : 'Last confirmed '.$report->heartbeatAt->toDayDateTimeString().' UTC.',
                'Delayed',
                $url,
                $report->heartbeatAt,
            );
        }

        return $banners;
    }

    private function maintenance(Maintenance $maintenance, BannerKind $kind, BannerLevel $level, string $label, ?string $url): Banner
    {
        return new Banner(
            $kind,
            $level,
            $maintenance->id,
            $maintenance->title,
            $maintenance->body === '' ? null : $maintenance->body,
            $label,
            $url,
            $maintenance->scheduledFor,
            $maintenance->scheduledUntil,
        );
    }

    /** A state this client does not know yet is labelled with its own words. */
    private function stateLabel(IncidentState $state, string $raw): string
    {
        return $state === IncidentState::Unknown ? ucfirst(str_replace('_', ' ', $raw)) : $state->label();
    }

    /**
     * @param  list<string>  $componentIds
     */
    private function relevant(array $componentIds): bool
    {
        return $this->components === [] || $componentIds === [] || array_intersect($componentIds, $this->components) !== [];
    }

    /** Only http(s) links ever reach an href. */
    private function safeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array($scheme, ['https', 'http'], true) ? $url : null;
    }
}
