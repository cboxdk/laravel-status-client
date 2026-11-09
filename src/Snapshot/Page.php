<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Snapshot;

use Cbox\StatusClient\Support\Field;

/**
 * The `page` object: which status page this is and where people read it.
 */
readonly class Page
{
    public function __construct(
        public string $id = '',
        public string $name = '',
        public string $url = '',
        public string $timezone = 'UTC',
        public ?string $description = null,
        public ?string $websiteUrl = null,
        public ?string $brandColor = null,
    ) {}

    /**
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data, string $path = 'page'): self
    {
        return new self(
            id: Field::string($data, 'id', $path),
            name: Field::string($data, 'name', $path),
            url: Field::string($data, 'url', $path),
            timezone: Field::string($data, 'timezone', $path),
            description: Field::nullableString($data, 'description', $path),
            websiteUrl: Field::nullableString($data, 'website_url', $path),
            brandColor: Field::nullableString($data, 'brand_color', $path),
        );
    }

    /**
     * @return array{id: string, name: string, description: ?string, url: string, website_url: ?string, timezone: string, brand_color: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'website_url' => $this->websiteUrl,
            'timezone' => $this->timezone,
            'brand_color' => $this->brandColor,
        ];
    }
}
