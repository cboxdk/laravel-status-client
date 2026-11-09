<?php

declare(strict_types=1);

namespace Cbox\StatusClient\Emergency;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Cbox\StatusClient\Support\Field;

/**
 * A verified emergency notice: an operator posted it with the offline CLI while the
 * control plane was down. Only ever built from a payload whose signature verified.
 */
readonly class EmergencyNotice
{
    public function __construct(
        public string $pageId,
        public string $message,
        public CarbonImmutable $postedAt,
        public ?CarbonImmutable $expiresAt = null,
    ) {}

    /**
     * Null unless every field the shell and the Go CLI agree on is there and typed.
     *
     * @param  array<mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $pageId = $data['page_id'] ?? null;
        $message = $data['message'] ?? null;
        $postedAt = Field::parseTime($data['posted_at'] ?? null);
        $hasExpiry = ($data['expires_at'] ?? null) !== null;
        $expiresAt = Field::parseTime($data['expires_at'] ?? null);

        if (! is_string($pageId) || ! is_string($message) || trim($message) === '' || $postedAt === null || ($hasExpiry && $expiresAt === null)) {
            return null;
        }

        return new self($pageId, $message, $postedAt, $expiresAt);
    }

    public function expired(CarbonInterface $now): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->lessThanOrEqualTo($now);
    }

    /**
     * @return array{page_id: string, message: string, posted_at: string, expires_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'page_id' => $this->pageId,
            'message' => $this->message,
            'posted_at' => $this->postedAt->toIso8601ZuluString(),
            'expires_at' => $this->expiresAt?->toIso8601ZuluString(),
        ];
    }
}
