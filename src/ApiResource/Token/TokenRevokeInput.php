<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Token;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class TokenRevokeInput
{
    /** Refresh token to invalidate. */
    #[Groups(['token:revoke'])]
    #[Assert\NotBlank(groups: ['token:revoke'])]
    #[Assert\Length(max: 4096, groups: ['token:revoke'])]
    public string $refreshToken = '';
}
