---
title: Requirements
description: Runtime requirements enforced by the package's composer constraints.
weight: 3
---

# Requirements

The package installs only when your project satisfies the constraints in its
`composer.json`. These are the versions the resolver enforces, nothing more.

## PHP

- **PHP `^8.4`**.
- **`ext-sodium`**: Ed25519 verification of emergency notices. Bundled with PHP since 7.2
  and enabled in most builds.

## Laravel

- **Laravel 12 or 13**: the package requires the `illuminate/*` components below at
  `^12.0 || ^13.0`.

## Direct dependencies

- **`illuminate/cache`, `illuminate/console`, `illuminate/contracts`, `illuminate/http`,
  `illuminate/support`, `illuminate/view`** `^12.0 || ^13.0`: the cache, the
  `status:check` command, the HTTP client, the Blade component.

No other runtime dependencies.

## Services

- A Cbox Status page. Its JSON is public, so no account or key is needed to read it.
- A cache store (any Laravel store; the default unless `cbox-status.cache.store` says otherwise).
