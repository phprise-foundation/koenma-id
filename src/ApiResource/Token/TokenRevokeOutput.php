<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Token;

use Symfony\Component\Serializer\Attribute\Groups;

final class TokenRevokeOutput
{
    /** Whether the refresh token was revoked. */
    #[Groups(['token:get'])]
    public bool $revoked = false;
}
