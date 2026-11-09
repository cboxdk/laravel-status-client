<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Facades\Status;
use Cbox\StatusClient\Snapshot\ComponentStatus;
use Cbox\StatusClient\Snapshot\IncidentImpact;
use Cbox\StatusClient\Testing\FakeStatus;
use Illuminate\Support\Facades\Blade;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-10T10:00:00Z'));
});

it('renders nothing when all is clear', function (): void {
    $fake = $this->fakeStatus();

    expect(trim(Blade::render('<x-cbox-status::banner />')))->toBe('');
    $fake->assertRead(1);

    expect(Status::report()->allClear())->toBeTrue();
    $fake->assertRead(2);
});

it('renders an escaped, accessible banner for an incident', function (): void {
    $this->fakeStatus()->withIncident('API <script>alert(1)</script>', IncidentImpact::Major, 'We are <b>on it</b>.');

    $html = Blade::render('<x-cbox-status::banner class="mb-4" />');

    expect($html)->toContain('role="alert"')
        ->toContain('data-kind="incident"')
        ->toContain('class="cbox-status-banners mb-4"')
        ->toContain('API &lt;script&gt;alert(1)&lt;/script&gt;')
        ->toContain('We are &lt;b&gt;on it&lt;/b&gt;.')
        ->toContain('href="'.FakeStatus::URL.'"')
        ->toContain('<style>')
        ->not->toContain('<script>');
});

it('renders maintenance as a polite status without the stylesheet when asked', function (): void {
    $this->fakeStatus()->withMaintenance('Database upgrade', now()->subMinutes(10), now()->addHour());

    $html = Blade::render('<x-cbox-status::banner :styles="false" link-text="Details" />');

    expect($html)->toContain('role="status"')
        ->toContain('data-kind="maintenance"')
        ->toContain('Nov 10, 09:50</time> – <time')
        ->toContain('>Details</a>')
        ->not->toContain('<style>');
});

it('shares a plain array for Inertia', function (): void {
    $this->fakeStatus()
        ->withIndicator(ComponentStatus::PartialOutage)
        ->withIncident('Slow sign-in', IncidentImpact::Minor, components: ['signin'])
        ->withMaintenance('Network work', now()->addDay(), now()->addDay()->addHour())
        ->withEmergency('Full outage.');

    $shared = Status::shared();

    expect($shared['available'])->toBeTrue()
        ->and($shared['indicator'])->toBe('partial_outage')
        ->and($shared['description'])->toBe('Partial outage')
        ->and($shared['page'])->toBe(['name' => 'Example', 'url' => FakeStatus::URL])
        ->and($shared['emergency']['message'] ?? null)->toBe('Full outage.')
        ->and(array_column($shared['banners'], 'kind'))->toBe(['emergency', 'incident', 'upcoming_maintenance'])
        ->and($shared['banners'][1])->toMatchArray(['level' => 'warning', 'role' => 'status', 'title' => 'Slow sign-in', 'label' => 'Investigating']);

    expect(json_encode($shared))->toBeString();
});

it('honours the component filter from config', function (): void {
    config(['cbox-status.components' => 'billing']);
    $this->fakeStatus()
        ->withIncident('Sign-in down', components: ['signin'])
        ->withIncident('Billing slow', IncidentImpact::Minor, components: ['billing']);

    expect(array_column(Status::shared()['banners'], 'title'))->toBe(['Billing slow']);
});

it('reports an unavailable or stale page through the fake', function (): void {
    $fake = $this->fakeStatus()->stale();

    expect(Status::report()->stale)->toBeTrue()
        ->and(Status::report()->heartbeatAge())->toBe(3600);

    $fake->unavailable()->withEmergency('Still here.');

    expect(Status::report()->available())->toBeFalse()
        ->and(array_column(Status::shared()['banners'], 'kind'))->toBe(['emergency']);
});

it('serves a saved current.json', function (): void {
    $this->fakeStatus(FakeStatus::fromFile(__DIR__.'/../Fixtures/contract/current-incident.json'));

    expect(array_column(Status::shared()['banners'], 'title'))->toBe(['API errors', 'Slow sign-in', 'Billing database upgrade', 'Network maintenance']);
});

it('checks the page from the console', function (): void {
    $this->fakeStatus()->withIncident('API errors');

    $this->artisan('status:check')
        ->expectsOutputToContain('Example (https://status.example.test)')
        ->expectsOutputToContain('Heartbeat: 0s ago, fresh')
        ->expectsOutputToContain('Banner:    [danger] API errors (Investigating)')
        ->assertSuccessful();
});

it('fails the check when the page is stale or unreadable', function (): void {
    $fake = $this->fakeStatus()->stale();
    $this->artisan('status:check --cached')->assertFailed();

    $fake->unavailable();
    $this->artisan('status:check --json')->expectsOutputToContain('"healthy": false')->assertFailed();
});

it('says so when it is not configured', function (): void {
    config(['cbox-status.page' => null]);

    $this->artisan('status:check')
        ->expectsOutputToContain('Cbox Status is not configured')
        ->assertFailed();
});

it('does not read the page until something asks', function (): void {
    $this->fakeStatus()->assertNotRead();
});

it('shares an unknown indicator as unknown, with the raw value beside it', function (): void {
    $this->fakeStatus(FakeStatus::fromFile(__DIR__.'/../Fixtures/contract/current-unknown.json'));

    $shared = Status::shared();

    expect($shared['indicator'])->toBe('unknown')
        ->and($shared['indicator_value'])->toBe('read_only')
        ->and($shared['description'])->toBe('Read-only mode')
        ->and(Status::report()->allClear())->toBeFalse();

    $this->artisan('status:check')->expectsOutputToContain('Status:    Unknown (read_only), Read-only mode')->assertSuccessful();
});
