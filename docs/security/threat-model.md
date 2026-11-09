---
title: Threat model
description: What a hostile mirror or network can and cannot do to an app that shows status banners.
weight: 61
---

# Threat model

The client reads public, unauthenticated JSON. It treats every byte it reads as
untrusted, except an emergency notice whose signature verifies.

| Threat | What happens |
|--------|--------------|
| A mirror serves another page's snapshot | Skipped: `page.id` must match. |
| A mirror serves malformed JSON or breaks the contract | Skipped, like a down origin. |
| A snapshot carries HTML or script in a title or body | Rendered as text: Blade escapes it; `shared()` documents text only. |
| A snapshot carries a `javascript:` page URL | Dropped: banner links are `http(s)` only. |
| A snapshot points `links.heartbeat` at another host | Ignored: links must be origin-relative paths. |
| A mirror forges an emergency notice | Rejected: the signature must verify against a configured key. |
| A mirror replays an old notice | Rejected after its `expires_at`. A notice without `expires_at` stays valid until it is removed; this is the format's choice, not the client's. |
| A mirror deletes a notice or serves an old snapshot | Possible. Freshness flags a page whose heartbeat stopped, but a mirror can serve an old heartbeat too. The client trusts origins for availability, not for integrity of the snapshot. |
| Status origins are slow or down | Bounded by timeouts and the cache; stale-if-error serves the last good snapshot; a read never throws. |

## Scope

- `current.json` and the heartbeat are not signed in `cbox-status/v1`. Only emergency
  notices are. The client cannot detect a mirror that serves a consistent but false
  snapshot.
- The client never contacts the Cbox Status control plane and carries no credentials.
