@if ($styles)
<style>
.cbox-status-banners{--cbox-status-info:#1d4ed8;--cbox-status-info-bg:#eff6ff;--cbox-status-warning:#92400e;--cbox-status-warning-bg:#fffbeb;--cbox-status-danger:#991b1b;--cbox-status-danger-bg:#fef2f2;display:grid;gap:.5rem;font:inherit}
@media (prefers-color-scheme:dark){.cbox-status-banners{--cbox-status-info:#bfdbfe;--cbox-status-info-bg:#172554;--cbox-status-warning:#fde68a;--cbox-status-warning-bg:#451a03;--cbox-status-danger:#fecaca;--cbox-status-danger-bg:#450a0a}}
.cbox-status-banner{display:flex;flex-wrap:wrap;gap:.25rem .75rem;align-items:baseline;padding:.625rem .875rem;border-radius:.5rem;border-inline-start:4px solid var(--cbox-status-c);background:var(--cbox-status-bg);color:var(--cbox-status-c);line-height:1.4}
.cbox-status-banner[data-level=info]{--cbox-status-c:var(--cbox-status-info);--cbox-status-bg:var(--cbox-status-info-bg)}
.cbox-status-banner[data-level=warning]{--cbox-status-c:var(--cbox-status-warning);--cbox-status-bg:var(--cbox-status-warning-bg)}
.cbox-status-banner[data-level=danger]{--cbox-status-c:var(--cbox-status-danger);--cbox-status-bg:var(--cbox-status-danger-bg)}
.cbox-status-banner__label{font-size:.8125em;font-weight:600;text-transform:uppercase;letter-spacing:.03em}
.cbox-status-banner__body{flex-basis:100%}
.cbox-status-banner__link{color:inherit;text-decoration:underline;margin-inline-start:auto}
</style>
@endif
<div {{ $attributes->merge(['class' => 'cbox-status-banners']) }} data-cbox-status>
@foreach ($banners as $banner)
    <div class="cbox-status-banner" role="{{ $banner->level->role() }}" data-kind="{{ $banner->kind->value }}" data-level="{{ $banner->level->value }}">
        @if ($banner->label !== null)
            <span class="cbox-status-banner__label">{{ $banner->label }}</span>
        @endif
        <strong class="cbox-status-banner__title">{{ $banner->title }}</strong>
        @if ($banner->startsAt !== null && $banner->kind->value !== 'delayed')
            <span class="cbox-status-banner__time"><time datetime="{{ $banner->startsAt->toIso8601ZuluString() }}">{{ $banner->startsAt->format('M j, H:i') }}</time>@if ($banner->endsAt !== null && $banner->kind->value !== 'emergency') – <time datetime="{{ $banner->endsAt->toIso8601ZuluString() }}">{{ $banner->endsAt->format('M j, H:i') }}</time>@endif UTC</span>
        @endif
        @if ($banner->url !== null)
            <a class="cbox-status-banner__link" href="{{ $banner->url }}" rel="noopener">{{ $linkText }}</a>
        @endif
        @if ($banner->body !== null)
            <span class="cbox-status-banner__body">{{ \Illuminate\Support\Str::limit($banner->body, 300) }}</span>
        @endif
    </div>
@endforeach
</div>
