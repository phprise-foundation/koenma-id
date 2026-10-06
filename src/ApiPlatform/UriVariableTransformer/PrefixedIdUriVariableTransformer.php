<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiPlatform\UriVariableTransformer;

use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\UriVariableTransformerInterface;
use Phprise\KoenmaID\ValueObject\AbstractPrefixedId;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('api_platform.uri_variables.transformer')]
final class PrefixedIdUriVariableTransformer implements UriVariableTransformerInterface
{
    public function transform(mixed $value, array $types, array $context = []): AbstractPrefixedId
    {
        try {
            return $types[0]::fromString($value);
        } catch (\InvalidArgumentException $exception) {
            throw new InvalidUriVariableException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function supportsTransformation(mixed $value, array $types, array $context = []): bool
    {
        return \is_string($value) && is_a($types[0], AbstractPrefixedId::class, true);
    }
}
