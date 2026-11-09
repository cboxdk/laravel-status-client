<?php

declare(strict_types=1);

use Cbox\StatusClient\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function contractFile(string $name): string
{
    return __DIR__.'/Fixtures/contract/'.$name;
}

/**
 * @return array<mixed>
 */
function fixtureJson(string $name): array
{
    $data = json_decode((string) file_get_contents(contractFile($name)), true, 64, JSON_THROW_ON_ERROR);

    return is_array($data) ? $data : [];
}

/**
 * A minimal valid snapshot for the test page, generated at `$generatedAt`.
 *
 * @return array<mixed>
 */
function snapshot(string $generatedAt = '2026-11-10T09:59:00Z', string $page = '01pageid', array $fallbacks = []): array
{
    $current = fixtureJson('current-incident.json');
    $current['generated_at'] = $generatedAt;
    $current['page']['id'] = $page;
    $current['fallback_origins'] = $fallbacks;

    return $current;
}

/**
 * @return array{schema: string, written_at: string}
 */
function heartbeat(string $at): array
{
    return ['schema' => 'cbox-status/v1', 'written_at' => $at];
}
