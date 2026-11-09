---
title: Banners
description: What the default banner policy shows, in which order and at which level.
weight: 24
---

# Banners

`Contracts\Banners` turns a report into `list<Banner>`. `DefaultBanners`:

| Order | Kind | Level | When |
|-------|------|-------|------|
| 1 | `emergency` | danger | A verified emergency notice, even when no snapshot could be read |
| 2 | `incident` | critical/major: danger, minor or unknown: warning, none: info | Each active incident, worst impact first, then newest |
| 3 | `maintenance` | warning | Each maintenance in progress |
| 4 | `upcoming_maintenance` | info | Maintenance that starts within `banners.upcoming_within_hours` (48) |
| 5 | `delayed` | info | The page is stale, only with `banners.show_delayed` |

An incident banner's body is the newest update's body and its label is that update's
state ("Investigating", "Identified", …).

## Component filter

With `CBOX_STATUS_COMPONENTS` set, an incident shows when any update names one of those
components, and a maintenance when it lists one. Incidents and maintenance that name no
component are page-wide and always show.

## Accessibility

Each banner carries `role`: `alert` for danger, `status` otherwise, so a screen reader
interrupts only for what matters now. The Blade component renders it; an Inertia layout
gets it in `shared()['banners'][*]['role']`.

## Safety

Every string is text. Blade escapes it; render it as text in JavaScript, never as HTML.
`url` is always the page's own URL and only ever `http(s)`.
