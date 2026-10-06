<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Serializer\Normalizer;

use Phprise\KoenmaID\ValueObject\AbstractPrefixedId;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PrefixedIdNormalizer implements NormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>|string|int|float|bool|\ArrayObject<string, mixed>|null
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        return $data->toString();
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AbstractPrefixedId;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [AbstractPrefixedId::class => true];
    }
}
