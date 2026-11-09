<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Emergency\EmergencyEnvelope;
use Cbox\StatusClient\Emergency\SodiumEmergencyVerifier;

const NOW = '2026-11-10T10:00:00Z';

/**
 * A real Ed25519 keypair and an envelope in the CLI's format.
 *
 * @param  array<string, mixed>  $notice
 * @return array{0: EmergencyEnvelope, 1: string}
 */
function signedNotice(array $notice, ?string $keypair = null): array
{
    $keypair ??= sodium_crypto_sign_keypair();
    $payload = (string) json_encode($notice);
    $publicKey = base64_encode(sodium_crypto_sign_publickey($keypair));

    return [
        new EmergencyEnvelope(base64_encode($payload), base64_encode(sodium_crypto_sign_detached($payload, sodium_crypto_sign_secretkey($keypair))), $publicKey),
        $publicKey,
    ];
}

$notice = ['page_id' => '01pageid', 'message' => 'We are aware of a full outage.', 'posted_at' => '2026-11-10T09:50:00Z', 'expires_at' => null];

it('verifies a notice signed by the Go emergency CLI', function (): void {
    $fixture = fixtureJson('emergency-go.json');
    $envelope = EmergencyEnvelope::fromArray(is_array($fixture['envelope']) ? $fixture['envelope'] : []);
    $key = is_string($fixture['public_key']) ? $fixture['public_key'] : '';

    $notice = (new SodiumEmergencyVerifier([$key]))->verify($envelope ?? new EmergencyEnvelope('', '', ''), '01pageid', CarbonImmutable::parse(NOW));

    expect($notice?->toArray())->toBe($fixture['notice']);
});

it('accepts a notice signed by a trusted key', function () use ($notice): void {
    [$envelope, $key] = signedNotice($notice);

    $verified = (new SodiumEmergencyVerifier(['someone-else', $key]))->verify($envelope, '01pageid', CarbonImmutable::parse(NOW));

    expect($verified?->message)->toBe('We are aware of a full outage.')
        ->and($verified?->expiresAt)->toBeNull();
});

it('rejects an untrusted key, a tampered payload, another page and an expired notice', function () use ($notice): void {
    $now = CarbonImmutable::parse(NOW);
    [$envelope, $key] = signedNotice($notice);
    $verifier = new SodiumEmergencyVerifier([$key]);

    expect((new SodiumEmergencyVerifier([]))->verify($envelope, '01pageid', $now))->toBeNull();

    $tampered = new EmergencyEnvelope(base64_encode((string) json_encode([...$notice, 'message' => 'All fine!'])), $envelope->signature, $envelope->key);
    expect($verifier->verify($tampered, '01pageid', $now))->toBeNull();

    [$other, $otherKey] = signedNotice([...$notice, 'page_id' => 'other']);
    expect((new SodiumEmergencyVerifier([$otherKey]))->verify($other, '01pageid', $now))->toBeNull();

    [$expired, $expiredKey] = signedNotice([...$notice, 'expires_at' => '2026-11-10T09:59:00Z']);
    expect((new SodiumEmergencyVerifier([$expiredKey]))->verify($expired, '01pageid', $now))->toBeNull();

    [$atExpiry, $atExpiryKey] = signedNotice([...$notice, 'expires_at' => NOW]);
    expect((new SodiumEmergencyVerifier([$atExpiryKey]))->verify($atExpiry, '01pageid', $now))->toBeNull();
});

it('rejects a signature by another key that claims a trusted one', function () use ($notice): void {
    [, $trusted] = signedNotice($notice);
    [$forged] = signedNotice($notice);

    $claimed = new EmergencyEnvelope($forged->payload, $forged->signature, $trusted);

    expect((new SodiumEmergencyVerifier([$trusted]))->verify($claimed, '01pageid', CarbonImmutable::parse(NOW)))->toBeNull();
});

it('rejects malformed envelopes without throwing', function (EmergencyEnvelope $envelope): void {
    expect((new SodiumEmergencyVerifier([$envelope->key]))->verify($envelope, '01pageid', CarbonImmutable::parse(NOW)))->toBeNull();
})->with([
    'not base64' => [new EmergencyEnvelope('%%%', '%%%', '%%%')],
    'short key' => [new EmergencyEnvelope(base64_encode('{}'), base64_encode(str_repeat('a', 64)), base64_encode('short'))],
    'short signature' => [new EmergencyEnvelope(base64_encode('{}'), base64_encode('short'), base64_encode(str_repeat('k', 32)))],
]);

it('rejects a signed payload that is not a notice', function (): void {
    $keypair = sodium_crypto_sign_keypair();
    $payload = '{"page_id":"01pageid"}';
    $key = base64_encode(sodium_crypto_sign_publickey($keypair));
    $envelope = new EmergencyEnvelope(base64_encode($payload), base64_encode(sodium_crypto_sign_detached($payload, sodium_crypto_sign_secretkey($keypair))), $key);

    expect((new SodiumEmergencyVerifier([$key]))->verify($envelope, '01pageid', CarbonImmutable::parse(NOW)))->toBeNull();
});

it('reads only envelope-shaped JSON', function (): void {
    expect(EmergencyEnvelope::fromArray(['payload' => 'a', 'signature' => 'b']))->toBeNull()
        ->and(EmergencyEnvelope::fromArray(['payload' => 'a', 'signature' => 'b', 'key' => 1]))->toBeNull()
        ->and(EmergencyEnvelope::fromArray(['payload' => 'a', 'signature' => 'b', 'key' => 'c'])?->key)->toBe('c');
});
