<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\ApiKey;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ApiKeyPatchInput
{
    /** New human readable name of the API Key. */
    #[Groups(['api_key:patch'])]
    #[Assert\Length(max: 255, groups: ['api_key:patch'])]
    public ?string $name = null;
}
