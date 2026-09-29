<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

final readonly class VerifiedToken
{
    private function __construct(
        public bool $valid,
        public ?string $username,
        public ?\DateTimeImmutable $expiresAt,
    ) {
    }

    public static function valid(string $username, int $expiresAtTimestamp): self
    {
        $expiresAt = 0 === $expiresAtTimestamp
            ? null
            : (new \DateTimeImmutable())->setTimestamp($expiresAtTimestamp);

        return new self(true, $username, $expiresAt);
    }

    public static function invalid(): self
    {
        return new self(false, null, null);
    }
}
