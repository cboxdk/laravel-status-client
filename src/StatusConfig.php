<?php

declare(strict_types=1);

namespace Cbox\StatusClient;

/**
 * The `cbox-status` config as typed values. Origins that are not http(s) URLs are
 * dropped; a page id or primary URL that is missing leaves the client unconfigured,
 * and an unconfigured client reads nothing.
 */
readonly class StatusConfig
{
    /**
     * @param  list<string>  $fallbacks
     * @param  list<string>  $emergencyKeys
     * @param  list<string>  $components
     */
    public function __construct(
        public string $pageId = '',
        public string $url = '',
        public array $fallbacks = [],
        public array $emergencyKeys = [],
        public array $components = [],
        public ?string $cacheStore = null,
        public int $ttl = 30,
        public int $errorTtl = 10,
        public int $keep = 86400,
        public float $timeout = 3.0,
        public float $connectTimeout = 2.0,
        public int $upcomingWithinHours = 48,
        public bool $showDelayed = false,
    ) {}

    /**
     * @param  array<mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $cache = is_array($config['cache'] ?? null) ? $config['cache'] : [];
        $http = is_array($config['http'] ?? null) ? $config['http'] : [];
        $banners = is_array($config['banners'] ?? null) ? $config['banners'] : [];
        $store = $cache['store'] ?? null;

        return new self(
            pageId: self::string($config['page'] ?? null),
            url: self::origin(self::string($config['url'] ?? null)) ?? '',
            fallbacks: array_values(array_filter(array_map(self::origin(...), self::list($config['fallbacks'] ?? null)))),
            emergencyKeys: self::list($config['emergency_keys'] ?? null),
            components: self::list($config['components'] ?? null),
            cacheStore: is_string($store) && $store !== '' ? $store : null,
            ttl: self::int($cache['ttl'] ?? null, 30, 1),
            errorTtl: self::int($cache['error_ttl'] ?? null, 10, 1),
            keep: self::int($cache['keep'] ?? null, 86400, 1),
            timeout: self::float($http['timeout'] ?? null, 3.0),
            connectTimeout: self::float($http['connect_timeout'] ?? null, 2.0),
            upcomingWithinHours: self::int($banners['upcoming_within_hours'] ?? null, 48, 0),
            showDelayed: filter_var($banners['show_delayed'] ?? false, FILTER_VALIDATE_BOOL),
        );
    }

    public function configured(): bool
    {
        return $this->pageId !== '' && $this->url !== '';
    }

    /**
     * The primary origin first, then each fallback, without duplicates.
     *
     * @return list<string>
     */
    public function origins(): array
    {
        return array_values(array_unique(array_filter([$this->url, ...$this->fallbacks])));
    }

    /** `https://status.example.com/` → `https://status.example.com`; null unless http(s). */
    public static function origin(string $url): ?string
    {
        $url = rtrim(trim($url), '/');
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (! in_array($scheme, ['https', 'http'], true) || ! is_string($host) || $host === '') {
            return null;
        }

        return $url;
    }

    private static function string(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * A list from config, or a comma or whitespace separated string from the environment.
     *
     * @return list<string>
     */
    private static function list(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $items[] = trim($item);
            }
        }

        return array_values(array_unique($items));
    }

    private static function int(mixed $value, int $default, int $min): int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($int) && $int >= $min ? $int : $default;
    }

    private static function float(mixed $value, float $default): float
    {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);

        return is_float($float) && $float > 0 ? $float : $default;
    }
}
