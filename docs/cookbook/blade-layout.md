---
title: Banner in a Blade layout
description: Put the banner at the top of every page, styled or unstyled.
weight: 31
---

# Banner in a Blade layout

```blade
<body>
    <x-cbox-status::banner class="mx-auto max-w-5xl" />
```

Attributes merge onto the wrapper `div`. Props:

| Prop | Default | |
|------|---------|--|
| `:styles` | `true` | A small inline stylesheet with light and dark colours as CSS variables (`--cbox-status-info`, `--cbox-status-danger-bg`, …). |
| `link-text` | `View status` | The link to the status page. |

With a strict Content Security Policy that blocks inline styles, use
`:styles="false"` and style `.cbox-status-banner[data-level=danger]` and friends
yourself. To change the markup, `php artisan vendor:publish --tag=cbox-status-views`.

The component renders nothing when there is nothing to say, so the layout needs no `@if`.
