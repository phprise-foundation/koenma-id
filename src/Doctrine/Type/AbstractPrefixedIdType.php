<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Doctrine\Type;

use Phprise\KoenmaID\ValueObject\AbstractPrefixedId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Uid\Ulid;

abstract class AbstractPrefixedIdType extends Type
{
    abstract protected function valueObjectClass(): string;

    public function generateValueObject(): AbstractPrefixedId
    {
        $class = $this->valueObjectClass();

        return $class::generate();
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        $identifier = $this->toValueObject($value);

        if (!$identifier instanceof AbstractPrefixedId) {
            return null;
        }

        return $identifier->toUlid()->toRfc4122();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AbstractPrefixedId
    {
        if (null === $value || $value instanceof AbstractPrefixedId) {
            return $value;
        }

        $class = $this->valueObjectClass();

        return $class::fromUlid(Ulid::fromString((string) $value));
    }

    private function toValueObject(mixed $value): ?AbstractPrefixedId
    {
        if ($value instanceof AbstractPrefixedId) {
            return $value;
        }

        if (!\is_string($value)) {
            return null;
        }

        $class = $this->valueObjectClass();

        try {
            return $class::fromString($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
