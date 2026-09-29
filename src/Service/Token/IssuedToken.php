<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

final readonly class IssuedToken
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
    ) {
    }
}
