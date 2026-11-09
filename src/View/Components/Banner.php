<?php

declare(strict_types=1);

namespace Cbox\StatusClient\View\Components;

use Cbox\StatusClient\Banners\Banner as StatusBanner;
use Cbox\StatusClient\StatusClient;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * `<x-cbox-status::banner />`: the active incident, maintenance and emergency banners,
 * or nothing at all when there are none. Text is escaped; links are http(s) only.
 * `:styles="false"` drops the small inline stylesheet (for a strict CSP, or your own).
 */
class Banner extends Component
{
    /** @var list<StatusBanner>|null */
    private ?array $resolved = null;

    public function __construct(
        private readonly StatusClient $status,
        public bool $styles = true,
        public string $linkText = 'View status',
    ) {}

    public function shouldRender(): bool
    {
        return $this->banners() !== [];
    }

    public function render(): View
    {
        return $this->view('cbox-status::components.banner', ['banners' => $this->banners()]);
    }

    /**
     * @return list<StatusBanner>
     */
    public function banners(): array
    {
        return $this->resolved ??= $this->status->banners();
    }
}
