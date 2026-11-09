<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Emergency;

use Carbon\CarbonInterface;
use Cbox\StatusClient\Contracts\EmergencyVerifier;
use JsonException;
use SodiumException;

/**
 * Ed25519 through libsodium (`sodium_crypto_sign_verify_detached`). The same checks as
 * the page shell (`shell/src/core.ts`), the Go CLI and the control plane's importer, in
 * the same order: trusted key, signature over the raw payload bytes, page, expiry.
 * With no trusted keys it verifies nothing.
 */
class SodiumEmergencyVerifier implements EmergencyVerifier
{
    /**
     * @param  list<string>  $trustedKeys  base64 raw Ed25519 public keys, as the page's "Emergency keys" lists them
     */
    public function __construct(private readonly array $trustedKeys) {}

    public function verify(EmergencyEnvelope $envelope, string $pageId, CarbonInterface $now): ?EmergencyNotice
    {
        if (! in_array($envelope->key, $this->trustedKeys, true)) {
            return null;
        }

        $publicKey = base64_decode($envelope->key, true);
        $signature = base64_decode($envelope->signature, true);
        $payload = base64_decode($envelope->payload, true);

        if ($publicKey === false || $signature === false || $payload === false
            || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return null;
        }

        try {
            if (! sodium_crypto_sign_verify_detached($signature, $payload, $publicKey)) {
                return null;
            }

            $data = json_decode($payload, true, 8, JSON_THROW_ON_ERROR);
        } catch (SodiumException|JsonException) {
            return null;
        }

        $notice = is_array($data) ? EmergencyNotice::fromArray($data) : null;

        if ($notice === null || $notice->pageId !== $pageId || $notice->expired($now)) {
            return null;
        }

        return $notice;
    }
}
