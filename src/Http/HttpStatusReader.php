<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Http;

use Carbon\CarbonImmutable;
use Cbox\StatusClient\Contracts\EmergencyVerifier;
use Cbox\StatusClient\Contracts\StatusReader;
use Cbox\StatusClient\Emergency\EmergencyEnvelope;
use Cbox\StatusClient\Exceptions\InvalidSnapshot;
use Cbox\StatusClient\Freshness;
use Cbox\StatusClient\Snapshot\Heartbeat;
use Cbox\StatusClient\Snapshot\Snapshot;
use Cbox\StatusClient\StatusConfig;
use Cbox\StatusClient\StatusReport;
use Cbox\StatusClient\Support\Field;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Throwable;

/**
 * Reads `current.json` over HTTP the way the page shell does: the primary origin first,
 * then each fallback, stopping at the first one whose heartbeat is fresh. When every
 * origin is stale, the newest snapshot wins. Fallback origins the snapshot itself
 * advertises are tried after the configured ones.
 *
 * Results are cached for a short TTL. The last good result is kept much longer and
 * served when every origin fails (stale-if-error); its freshness is still judged by the
 * heartbeat's age, so it turns stale on its own. Cache failures are ignored: the cache
 * is an optimisation, never a reason to fail a read.
 *
 * Only the public JSON is ever read. Nothing here knows the control plane exists.
 */
class HttpStatusReader implements StatusReader
{
    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly EmergencyVerifier $verifier,
        private readonly StatusConfig $config,
    ) {}

    public function read(): StatusReport
    {
        $cached = $this->cacheGet($this->key('fresh'));

        return is_array($cached) ? $this->report($cached) : $this->refresh();
    }

    public function refresh(): StatusReport
    {
        $fetched = $this->fetch();

        if (is_array($fetched['current'])) {
            $this->cachePut($this->key('fresh'), $fetched, $this->config->ttl);
            $this->cachePut($this->key('last'), $fetched, $this->config->keep);

            return $this->report($fetched);
        }

        $last = $this->cacheGet($this->key('last'));
        $payload = is_array($last) ? $last : $fetched;
        $payload['failed'] = true;
        $payload['emergency'] = $fetched['emergency'] ?? $payload['emergency'] ?? null;

        $this->cachePut($this->key('fresh'), $payload, $this->config->errorTtl);

        return $this->report($payload);
    }

    /**
     * @return array{origin: ?string, current: ?array<mixed>, heartbeat: ?array<mixed>, emergency: ?array<mixed>, fetched_at: string, failed: bool}
     */
    private function fetch(): array
    {
        $now = CarbonImmutable::now();
        $origins = $this->config->origins();
        $best = null;

        for ($i = 0; $i < count($origins); $i++) {
            $origin = $origins[$i];
            $current = $this->json($origin.'/p/'.rawurlencode($this->config->pageId).'/current.json');

            try {
                $snapshot = $current === null ? null : Snapshot::fromArray($current);
            } catch (InvalidSnapshot) {
                $snapshot = null;
            }

            if ($current === null || $snapshot === null || $snapshot->page->id !== $this->config->pageId) {
                continue;
            }

            foreach ($snapshot->fallbackOrigins as $advertised) {
                $advertised = StatusConfig::origin($advertised);

                if ($advertised !== null && ! in_array($advertised, $origins, true)) {
                    $origins[] = $advertised;
                }
            }

            $heartbeat = $this->json($origin.$this->path($snapshot->heartbeatPath, '/heartbeat.json'));
            $candidate = ['origin' => $origin, 'current' => $current, 'heartbeat' => $heartbeat, 'snapshot' => $snapshot];
            $beat = $heartbeat === null ? null : Heartbeat::fromArray($heartbeat);

            if (! Freshness::isStale($beat, $snapshot->staleAfterSeconds, $now)) {
                $best = $candidate;

                break;
            }

            if ($best === null || ($snapshot->generatedAt?->getTimestampMs() ?? 0) > ($best['snapshot']->generatedAt?->getTimestampMs() ?? 0)) {
                $best = $candidate;
            }
        }

        return [
            'origin' => $best['origin'] ?? null,
            'current' => $best['current'] ?? null,
            'heartbeat' => $best['heartbeat'] ?? null,
            'emergency' => $this->emergency($origins, $best['snapshot'] ?? null, $now),
            'fetched_at' => $now->toIso8601ZuluString(),
            'failed' => $best === null,
        ];
    }

    /**
     * The first envelope on any origin that verifies. Not fetched at all without
     * trusted keys: an unverifiable notice is never shown, so there is nothing to read.
     *
     * @param  list<string>  $origins
     * @return array{payload: string, signature: string, key: string}|null
     */
    private function emergency(array $origins, ?Snapshot $snapshot, CarbonImmutable $now): ?array
    {
        if ($this->config->emergencyKeys === []) {
            return null;
        }

        $default = '/p/'.rawurlencode($this->config->pageId).'/emergency.json';
        $path = $this->path($snapshot?->emergencyPath, $default);

        foreach ($origins as $origin) {
            $data = $this->json($origin.$path);
            $envelope = $data === null ? null : EmergencyEnvelope::fromArray($data);

            if ($envelope !== null && $this->verifier->verify($envelope, $this->config->pageId, $now) !== null) {
                return $envelope->toArray();
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function report(array $payload): StatusReport
    {
        $now = CarbonImmutable::now();
        $envelope = is_array($payload['emergency'] ?? null) ? EmergencyEnvelope::fromArray($payload['emergency']) : null;
        $emergency = $envelope === null ? null : $this->verifier->verify($envelope, $this->config->pageId, $now);
        $failed = ($payload['failed'] ?? false) === true;

        try {
            $snapshot = is_array($payload['current'] ?? null) ? Snapshot::fromArray($payload['current']) : null;
        } catch (InvalidSnapshot) {
            $snapshot = null;
        }

        if ($snapshot === null) {
            return StatusReport::unavailable($emergency, $failed);
        }

        $heartbeat = is_array($payload['heartbeat'] ?? null) ? Heartbeat::fromArray($payload['heartbeat']) : null;
        $origin = $payload['origin'] ?? null;

        return new StatusReport(
            snapshot: $snapshot,
            stale: Freshness::isStale($heartbeat, $snapshot->staleAfterSeconds, $now),
            heartbeatAt: $heartbeat?->writtenAt,
            emergency: $emergency,
            origin: is_string($origin) ? $origin : null,
            fetchedAt: Field::parseTime($payload['fetched_at'] ?? null),
            fetchFailed: $failed,
        );
    }

    /**
     * An origin-relative path from the snapshot's `links`, or the default. Anything that
     * could point at another host (`//evil`, `https://…`) is ignored.
     */
    private function path(?string $path, string $default): string
    {
        if ($path === null || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $default;
        }

        return $path;
    }

    /**
     * @return array<mixed>|null
     */
    private function json(string $url): ?array
    {
        try {
            $response = $this->http
                ->timeout($this->config->timeout)
                ->connectTimeout($this->config->connectTimeout)
                ->acceptJson()
                ->withHeaders(['Cache-Control' => 'no-cache'])
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = json_decode($response->body(), true, 64);

        return is_array($data) && ($data === [] || ! array_is_list($data)) ? $data : null;
    }

    private function key(string $kind): string
    {
        return 'cbox-status:'.hash('xxh128', $this->config->pageId.'|'.implode('|', $this->config->origins())).':'.$kind;
    }

    private function cacheGet(string $key): mixed
    {
        try {
            return $this->cache->get($key);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<mixed>  $value
     */
    private function cachePut(string $key, array $value, int $seconds): void
    {
        try {
            $this->cache->put($key, $value, $seconds);
        } catch (Throwable) {
            // A cache that is down only costs a refetch on the next read.
        }
    }
}
