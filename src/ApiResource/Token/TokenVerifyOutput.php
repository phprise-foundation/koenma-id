<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Token;

use Symfony\Component\Serializer\Attribute\Groups;

final class TokenVerifyOutput
{
    /** Whether the token is valid and not expired. */
    #[Groups(['token:get'])]
    public bool $valid = false;

    /** Username carried by the token, when valid. */
    #[Groups(['token:get'])]
    public ?string $username = null;

    /** Expiration date of the token in ISO 8601 format, when valid. */
    #[Groups(['token:get'])]
    public ?string $expiresAt = null;
}
