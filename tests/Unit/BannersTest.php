<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Banners\Banner;
use Cbox\StatusClient\Banners\BannerKind;
use Cbox\StatusClient\Banners\BannerLevel;
use Cbox\StatusClient\Banners\DefaultBanners;
use Cbox\StatusClient\Emergency\EmergencyNotice;
use Cbox\StatusClient\Snapshot\Page;
use Cbox\StatusClient\Snapshot\Snapshot;
use Cbox\StatusClient\StatusReport;

function incidentReport(bool $stale = false, ?EmergencyNotice $emergency = null): StatusReport
{
    return new StatusReport(
        snapshot: Snapshot::fromArray(fixtureJson('current-incident.json')),
        stale: $stale,
        heartbeatAt: CarbonImmutable::parse('2026-11-10T09:00:00Z'),
        emergency: $emergency,
    );
}

/**
 * @param  list<Banner>  $banners
 * @return list<string>
 */
function ids(array $banners): array
{
    return array_map(fn (Banner $banner): string => $banner->id, $banners);
}

$now = CarbonImmutable::parse('2026-11-10T10:00:00Z');

it('lists incidents worst first, then maintenance in progress, then maintenance starting soon', function () use ($now): void {
    $banners = (new DefaultBanners)->for(incidentReport(), $now);

    expect(ids($banners))->toBe(['inc-major', 'inc-minor', 'mnt-now', 'mnt-soon'])
        ->and($banners[0]->level)->toBe(BannerLevel::Danger)
        ->and($banners[0]->body)->toBe('A database failover is in progress.')
        ->and($banners[0]->label)->toBe('Identified')
        ->and($banners[0]->url)->toBe('https://acme.cboxstatus.com')
        ->and($banners[1]->level)->toBe(BannerLevel::Warning)
        ->and($banners[1]->body)->toBe('A fix is out; we are watching it.')
        ->and($banners[2]->kind)->toBe(BannerKind::Maintenance)
        ->and($banners[3]->kind)->toBe(BannerKind::UpcomingMaintenance)
        ->and($banners[3]->body)->toBeNull()
        ->and($banners[3]->toArray()['role'])->toBe('status')
        ->and($banners[0]->toArray()['role'])->toBe('alert');
});

it('puts a verified emergency notice first', function () use ($now): void {
    $notice = new EmergencyNotice('01pageid', 'Full outage.', $now->subMinutes(5));

    $banners = (new DefaultBanners)->for(incidentReport(emergency: $notice), $now);

    expect($banners[0]->kind)->toBe(BannerKind::Emergency)
        ->and($banners[0]->body)->toBe('Full outage.');
});

it('shows an emergency notice even when no snapshot could be read', function () use ($now): void {
    $notice = new EmergencyNotice('01pageid', 'Full outage.', $now->subMinutes(5));

    expect(ids((new DefaultBanners)->for(StatusReport::unavailable($notice), $now)))->toBe(['emergency'])
        ->and((new DefaultBanners)->for(new StatusReport, $now))->toBe([]);
});

it('filters by component and keeps page-wide notices', function () use ($now): void {
    expect(ids((new DefaultBanners(['cmp-api']))->for(incidentReport(), $now)))->toBe(['inc-major', 'mnt-soon'])
        ->and(ids((new DefaultBanners(['cmp-docs'], upcomingWithinHours: 24 * 14))->for(incidentReport(), $now)))->toBe(['mnt-soon', 'mnt-later']);
});

it('says the page may be delayed only when asked to', function () use ($now): void {
    expect(ids((new DefaultBanners)->for(incidentReport(stale: true), $now)))->not->toContain('delayed')
        ->and(ids((new DefaultBanners(showDelayed: true))->for(incidentReport(stale: true), $now)))->toContain('delayed')
        ->and(ids((new DefaultBanners(showDelayed: true))->for(incidentReport(), $now)))->not->toContain('delayed');
});

it('never links anything but http(s)', function () use ($now): void {
    $report = new StatusReport(snapshot: new Snapshot(page: new Page('p', 'P', 'javascript:alert(1)')), stale: false);
    $notice = new EmergencyNotice('p', 'x', $now);

    expect((new DefaultBanners)->for(new StatusReport($report->snapshot, false, emergency: $notice), $now)[0]->url)->toBeNull();
});
