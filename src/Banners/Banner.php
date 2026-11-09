<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Banners;

use Carbon\CarbonImmutable;

/**
 * One line of "something is going on" for the host app's chrome. Plain text only: the
 * Blade component escapes it, and an Inertia page renders it as text.
 */
readonly class Banner
{
    public function __construct(
        public BannerKind $kind,
        public BannerLevel $level,
        public string $id,
        public string $title,
        public ?string $body = null,
        public ?string $label = null,
        public ?string $url = null,
        public ?CarbonImmutable $startsAt = null,
        public ?CarbonImmutable $endsAt = null,
    ) {}

    /**
     * @return array{kind: string, level: string, role: string, id: string, title: string, body: ?string, label: ?string, url: ?string, starts_at: ?string, ends_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind->value,
            'level' => $this->level->value,
            'role' => $this->level->role(),
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'label' => $this->label,
            'url' => $this->url,
            'starts_at' => $this->startsAt?->toIso8601ZuluString(),
            'ends_at' => $this->endsAt?->toIso8601ZuluString(),
        ];
    }
}
