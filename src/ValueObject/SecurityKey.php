<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ValueObject;

final readonly class SecurityKey
{
    private const string PATTERN = '/^sk_[A-Za-z0-9]{32}$/';

    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException('Security key must match the pattern sk_ followed by 32 alphanumeric characters.');
        }

        return new self($value);
    }

    public function hash(): string
    {
        return hash('sha256', $this->value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
