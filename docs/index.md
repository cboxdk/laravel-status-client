---
title: Introduction
description: Read a Cbox Status page's public JSON from any Laravel app and show incident, maintenance and emergency banners.
weight: 1
---

# Cbox Status for Laravel

Cbox Status publishes every status page as static JSON (`current.json`, contract
`cbox-status/v1`) to more than one storage origin. This package reads that JSON from a
Laravel app and turns it into typed values and ready-made banners, so any Cbox app can
say "we have an incident" or "maintenance tonight" in its own chrome.

It reads only the public files any visitor's browser reads. It has no API key, and it
never talks to the Cbox Status control plane.

## What you get

- **Typed snapshot.** `current.json` as value objects: page, overall status, components,
  active and recent incidents, active and upcoming maintenance.
- **Origin fallback.** The page's own domain first, then each mirror you configure, then
  mirrors the snapshot itself lists. See [Freshness and fallback](core-concepts/freshness-and-fallback.md).
- **Freshness.** A page is stale when its heartbeat is older than `stale_after_seconds`.
- **Emergency notices.** Notices signed offline with Ed25519 are shown only when one of
  your configured keys verifies them. See [Emergency notices](core-concepts/emergency-notices.md).
- **Cache.** A short TTL, plus the last good snapshot kept for stale-if-error.
- **Banners.** `<x-cbox-status::banner />` for Blade, `Status::shared()` for Inertia.
- **Testing.** `FakeStatus` and `InteractsWithStatus`. The package's own tests use them.
- **Probe.** `php artisan status:check`.

## Documentation

- [Quickstart](quickstart.md) and [Requirements](requirements.md)
- [Getting started](getting-started/_index.md): installation, testing
- [Core concepts](core-concepts/_index.md): architecture, freshness and fallback, emergency notices, banners
- [Cookbook](cookbook/_index.md): Blade layout, Inertia, probe and warm cache
- [Extension points](extension-points/_index.md): the contracts you can rebind
- [Configuration](configuration/_index.md): every key
- [Security](security/_index.md): threat model and honest scope
