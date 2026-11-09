<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Support;

use BackedEnum;
use Carbon\CarbonImmutable;
use Cbox\StatusClient\Exceptions\InvalidSnapshot;
use Throwable;

/**
 * Typed reads from a decoded JSON object. Everything that crosses the wire goes
 * through here once, on the way into a value object; nothing past the value
 * objects sees an array. Unknown keys are ignored: the contract only grows.
 *
 * @internal
 */
class Field
{
    private const string TIMESTAMP = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/';

    /**
     * @param  array<mixed>  $data
     */
    public static function string(array $data, string $key, string $path): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw InvalidSnapshot::field($path.'.'.$key, 'a string');
        }

        return $value;
    }

    /**
     * A key the contract allows to be null or, when it is not in `required`, absent.
     *
     * @param  array<mixed>  $data
     */
    public static function nullableString(array $data, string $key, string $path): ?string
    {
        $value = $data[$key] ?? null;

        if ($value !== null && ! is_string($value)) {
            throw InvalidSnapshot::field($path.'.'.$key, 'a string or null');
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function positiveInt(array $data, string $key, string $path): int
    {
        $value = $data[$key] ?? null;

        if (! is_int($value) || $value < 1) {
            throw InvalidSnapshot::field($path.'.'.$key, 'an integer of at least 1');
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public static function object(array $data, string $key, string $path): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            throw InvalidSnapshot::field($path.'.'.$key, 'an object');
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<array<mixed>>
     */
    public static function objects(array $data, string $key, string $path): array
    {
        $items = [];

        foreach (self::list($data, $key, $path) as $index => $item) {
            if (! is_array($item) || ($item !== [] && array_is_list($item))) {
                throw InvalidSnapshot::field(sprintf('%s.%s[%d]', $path, $key, $index), 'an object');
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param  array<mixed>  $data
     * @return list<string>
     */
    public static function strings(array $data, string $key, string $path): array
    {
        $items = [];

        foreach (self::list($data, $key, $path) as $index => $item) {
            if (! is_string($item)) {
                throw InvalidSnapshot::field(sprintf('%s.%s[%d]', $path, $key, $index), 'a string');
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function time(array $data, string $key, string $path): CarbonImmutable
    {
        return self::parseTime($data[$key] ?? null)
            ?? throw InvalidSnapshot::field($path.'.'.$key, 'an RFC 3339 timestamp');
    }

    /**
     * @param  array<mixed>  $data
     */
    public static function nullableTime(array $data, string $key, string $path): ?CarbonImmutable
    {
        $value = $data[$key] ?? null;

        return $value === null ? null : self::time($data, $key, $path);
    }

    /**
     * A contract enum. v1 is additive, and a new value is an additive change, so a value
     * this client does not know yet maps to `$unknown` instead of failing the snapshot.
     * The caller keeps the raw string next to the case.
     *
     * @template T of BackedEnum
     *
     * @param  array<mixed>  $data
     * @param  class-string<T>  $enum
     * @param  T  $unknown
     * @return T
     */
    public static function enum(array $data, string $key, string $enum, BackedEnum $unknown, string $path): BackedEnum
    {
        return $enum::tryFrom(self::string($data, $key, $path)) ?? $unknown;
    }

    public static function parseTime(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match(self::TIMESTAMP, $value) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<mixed>  $data
     * @return list<mixed>
     */
    private static function list(array $data, string $key, string $path): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value) || ! array_is_list($value)) {
            throw InvalidSnapshot::field($path.'.'.$key, 'an array');
        }

        return $value;
    }
}
