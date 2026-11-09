<?php

declare(strict_types=1);

/*
 * Cbox Status for Laravel. Every value here is public: the client reads a status page's
 * public JSON, the same files any visitor's browser reads, and nothing else.
 */
return [

    // The page id (as in /p/{page}/current.json) and the origin that serves it: the
    // page's own domain, e.g. https://cbox.cboxstatus.com. Without both, nothing is read.
    'page' => env('CBOX_STATUS_PAGE'),
    'url' => env('CBOX_STATUS_URL'),

    // Origins tried in order when the primary is down or stale, e.g. the mirror at
    // https://data.cboxstatus.com. Comma separated in the environment. Origins the
    // snapshot itself lists in `fallback_origins` are tried after these.
    'fallbacks' => env('CBOX_STATUS_FALLBACKS', ''),

    // Base64 Ed25519 public keys from the page's "Emergency keys". A signed emergency
    // notice is shown only when one of these signed it. Empty: notices are not read.
    'emergency_keys' => env('CBOX_STATUS_EMERGENCY_KEYS', ''),

    // Component ids this app cares about. Empty: every incident and maintenance shows.
    'components' => env('CBOX_STATUS_COMPONENTS', ''),

    'cache' => [
        'store' => env('CBOX_STATUS_CACHE_STORE'),   // null: the default store
        'ttl' => (int) env('CBOX_STATUS_CACHE_TTL', 30),
        'error_ttl' => 10,     // how long a failed fetch is remembered before retrying
        'keep' => 86400,       // how long the last good snapshot is kept for stale-if-error
    ],

    'http' => [
        'timeout' => 3,
        'connect_timeout' => 2,
    ],

    'banners' => [
        // Upcoming maintenance shows this many hours before it starts.
        'upcoming_within_hours' => 48,

        // Show "status updates may be delayed" when the page's heartbeat is stale.
        'show_delayed' => false,
    ],

];
