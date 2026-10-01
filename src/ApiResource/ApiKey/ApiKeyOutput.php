<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\ApiKey;

use Phprise\KoenmaID\Entity\ApiKey;
use Symfony\Component\Serializer\Attribute\Groups;

final class ApiKeyOutput
{
    /** Unique identifier of the API Key. */
    #[Groups(['api_key:get'])]
    public string $id = '';

    /** Identifier of the project that owns the API Key. */
    #[Groups(['api_key:get'])]
    public string $projectId = '';

    /** Human readable name of the API Key. */
    #[Groups(['api_key:get'])]
    public string $name = '';

    /** First characters of the plain key, kept for identification. */
    #[Groups(['api_key:get'])]
    public string $keyPrefix = '';

    /** Last characters of the plain key, kept for identification. */
    #[Groups(['api_key:get'])]
    public string $keySuffix = '';

    /** Expiration date in ISO 8601 format, or null when the key never expires. */
    #[Groups(['api_key:get'])]
    public ?string $expiresAt = null;

    /** Creation date in ISO 8601 format. */
    #[Groups(['api_key:get'])]
    public string $createdAt = '';

    /** Plain security key. Returned only in the create response and never stored. Store it now, because it cannot be recovered. */
    #[Groups(['api_key:post'])]
    public ?string $securityKey = null;

    public static function fromEntity(ApiKey $apiKey): self
    {
        $output = new self();
        $output->id = (string) $apiKey->id();
        $output->projectId = (string) $apiKey->project()->id();
        $output->name = $apiKey->name();
        $output->keyPrefix = $apiKey->keyPrefix();
        $output->keySuffix = $apiKey->keySuffix();
        $output->expiresAt = $apiKey->expiresAt()?->format(\DateTimeInterface::ATOM);
        $output->createdAt = $apiKey->createdAt()->format(\DateTimeInterface::ATOM);

        return $output;
    }

    public static function fromIssued(ApiKey $apiKey, string $plainKey): self
    {
        $output = self::fromEntity($apiKey);
        $output->securityKey = $plainKey;

        return $output;
    }
}
