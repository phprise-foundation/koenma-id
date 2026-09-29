<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class TokenVerifier
{
    public function __construct(private JWTTokenManagerInterface $jwtManager)
    {
    }

    public function verify(string $token): VerifiedToken
    {
        try {
            $payload = $this->jwtManager->parse($token);
        } catch (JWTDecodeFailureException) {
            return VerifiedToken::invalid();
        }

        return VerifiedToken::valid(
            (string) ($payload['username'] ?? ''),
            (int) ($payload['exp'] ?? 0),
        );
    }
}
