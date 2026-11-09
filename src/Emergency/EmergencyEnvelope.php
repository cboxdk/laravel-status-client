<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Emergency;

use Cbox\StatusClient\Contracts\EmergencyVerifier;

/**
 * What the offline `cbox-status` CLI writes to `p/{page}/emergency.json` on every mirror:
 * base64 of the exact payload bytes that were signed, the base64 Ed25519 signature over
 * those bytes, and the base64 raw public key that signed them. Verification never
 * depends on JSON canonicalisation, because the signature covers the bytes as written.
 */
readonly class EmergencyEnvelope
{
    public function __construct(
        public string $payload,
        public string $signature,
        public string $key,
    ) {}

    /**
     * Null for anything that is not shaped like an envelope. Shape only: whether it is
     * genuine is {@see EmergencyVerifier}'s question.
     *
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $payload = $data['payload'] ?? null;
        $signature = $data['signature'] ?? null;
        $key = $data['key'] ?? null;

        if (! is_string($payload) || ! is_string($signature) || ! is_string($key)) {
            return null;
        }

        return new self($payload, $signature, $key);
    }

    /**
     * @return array{payload: string, signature: string, key: string}
     */
    public function toArray(): array
    {
        return ['payload' => $this->payload, 'signature' => $this->signature, 'key' => $this->key];
    }
}
