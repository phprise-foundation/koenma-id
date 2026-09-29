<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Token;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class TokenRefreshInput
{
    /** Refresh token issued by the create or refresh operation. */
    #[Groups(['token:refresh'])]
    #[Assert\NotBlank(groups: ['token:refresh'])]
    public string $refreshToken = '';
}
