<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ValueObject;

use Symfony\Component\Uid\Ulid;

abstract class AbstractPrefixedId
{
    final protected function __construct(private readonly Ulid $ulid)
    {
    }

    abstract protected static function prefix(): string;

    public static function generate(): static
    {
        return new static(new Ulid());
    }

    public static function fromString(string $value): static
    {
        $prefix = static::prefix();

        if (!str_starts_with($value, $prefix)) {
            throw new \InvalidArgumentException(\sprintf('Identifier must start with "%s".', $prefix));
        }

        return new static(Ulid::fromString(substr($value, \strlen($prefix))));
    }

    public static function fromUlid(Ulid $ulid): static
    {
        return new static($ulid);
    }

    public function toUlid(): Ulid
    {
        return $this->ulid;
    }

    public function toString(): string
    {
        return static::prefix().$this->ulid->toBase32();
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
