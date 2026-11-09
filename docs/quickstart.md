---
title: Quickstart
description: From composer require to an incident banner in your layout.
weight: 2
---

# Quickstart

```bash
composer require cboxdk/laravel-status-client
```

Point it at a page. The page id is the one in `/p/{page}/current.json`:

```dotenv
CBOX_STATUS_PAGE=01m708vt2qwrtpzk61e80vaby8
CBOX_STATUS_URL=https://cbox.cboxstatus.com
CBOX_STATUS_FALLBACKS=https://data.cboxstatus.com
```

Add the banner to your layout:

```blade
<body>
    <x-cbox-status::banner />
    …
```

It renders nothing while all is clear. During an incident or maintenance it renders
one line per item, most urgent first, linked to the status page.

Check it from the console:

```bash
php artisan status:check
```

Using Inertia instead? Share the array and render it in your layout component:

```php
Inertia::share('status', fn () => \Cbox\StatusClient\Facades\Status::shared());
```

Next: [Configuration](configuration/reference.md), [Testing](getting-started/testing.md).
