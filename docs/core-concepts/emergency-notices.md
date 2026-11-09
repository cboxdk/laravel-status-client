---
title: Emergency notices
description: Notices signed offline with Ed25519, and the checks before one is shown.
weight: 23
---

# Emergency notices

When the Cbox Status control plane is down, an operator can post a notice with the
offline `cbox-status` CLI. It writes `p/{page}/emergency.json` straight to the
mirrors:

```json
{ "payload": "<base64 JSON>", "signature": "<base64 Ed25519>", "key": "<base64 public key>" }
```

The payload is `{"page_id", "message", "posted_at", "expires_at"}`. The signature
covers the payload bytes exactly as written, so no JSON canonicalisation is involved.

## Checks

`SodiumEmergencyVerifier` shows a notice only when all of these hold:

1. `key` is one of `CBOX_STATUS_EMERGENCY_KEYS`.
2. The key is 32 bytes, the signature 64, and `sodium_crypto_sign_verify_detached`
   accepts the signature over the payload.
3. The payload is a notice with a non-empty message and valid timestamps.
4. `page_id` is this page.
5. `expires_at` is null or later than now.

These are the same checks as the page shell, the Go CLI and the control plane's
importer. The test suite verifies the Go CLI's signed fixture.

Without configured keys, notices are not fetched at all. A mirror that is compromised
can delete a notice, but cannot forge one.

## Getting the keys

Copy the public keys from the page's "Emergency keys" in Cbox Status. They are public;
putting them in `.env` or in the config file is fine.
