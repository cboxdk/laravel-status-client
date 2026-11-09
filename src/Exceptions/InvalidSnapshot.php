<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Exceptions;

use RuntimeException;

/**
 * A document did not match the public contract `cbox-status/v1`. The reader treats
 * it like an unreachable origin and moves on to the next one.
 */
class InvalidSnapshot extends RuntimeException
{
    public static function field(string $path, string $expected): self
    {
        return new self(sprintf('cbox-status/v1: "%s" must be %s.', $path, $expected));
    }
}
