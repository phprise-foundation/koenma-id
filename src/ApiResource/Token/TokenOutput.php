<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Token;

use Symfony\Component\Serializer\Attribute\Groups;

final class TokenOutput
{
    /** Signed JWT used to authenticate the following requests. */
    #[Groups(['token:get'])]
    public string $accessToken = '';

    /** Opaque token used to obtain a new access token after expiration. */
    #[Groups(['token:get'])]
    public string $refreshToken = '';

    /** Authentication scheme expected in the Authorization header. */
    #[Groups(['token:get'])]
    public string $tokenType = 'Bearer';

    /** Lifetime of the access token in seconds. */
    #[Groups(['token:get'])]
    public int $expiresIn = 0;
}
