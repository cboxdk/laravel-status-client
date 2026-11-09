---
title: Banner in an Inertia app
description: Share the banners with every page and render them in your layout.
weight: 32
---

# Banner in an Inertia app

Share lazily, so pages that do not render the layout do not read the cache:

```php
// app/Http/Middleware/HandleInertiaRequests.php
use Cbox\StatusClient\Facades\Status;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'status' => fn () => Status::shared(),
    ];
}
```

The shape:

```ts
type StatusShared = {
  available: boolean
  stale: boolean
  // 'unknown' when the page uses a status newer than this package; never treat it as operational
  indicator: 'operational' | 'degraded_performance' | 'partial_outage' | 'major_outage' | 'under_maintenance' | 'unknown' | null
  indicator_value: string | null // the raw value from the page, e.g. 'read_only'
  description: string | null
  page: { name: string; url: string } | null
  emergency: { page_id: string; message: string; posted_at: string; expires_at: string | null } | null
  banners: {
    kind: 'emergency' | 'incident' | 'maintenance' | 'upcoming_maintenance' | 'delayed'
    level: 'info' | 'warning' | 'danger'
    role: 'alert' | 'status'
    id: string
    title: string
    body: string | null
    label: string | null
    url: string | null
    starts_at: string | null
    ends_at: string | null
  }[]
}
```

A React layout:

```tsx
{status.banners.map((b) => (
  <div key={b.kind + b.id} role={b.role} data-level={b.level}>
    {b.label && <span>{b.label}</span>} <strong>{b.title}</strong>
    {b.body && <p>{b.body}</p>}
    {b.url && <a href={b.url}>View status</a>}
  </div>
))}
```

Render every field as text. Handle `indicator: 'unknown'` with a neutral style and the
page's `description`; never fall back to "all systems operational". Never pass `body` to `dangerouslySetInnerHTML` or `v-html`.
