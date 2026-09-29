<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\ApiKey;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ApiKeyInput
{
    /** Human readable name of the API Key. */
    #[Groups(['api_key:post'])]
    #[Assert\NotBlank(groups: ['api_key:post'])]
    #[Assert\Length(max: 255, groups: ['api_key:post'])]
    public string $name = '';

    /** Number of days until the API Key expires. Omit it for a key that never expires. */
    #[Groups(['api_key:post'])]
    #[Assert\Positive(groups: ['api_key:post'])]
    public ?int $expiresInDays = null;
}
