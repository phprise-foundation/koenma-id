<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

use Phprise\KoenmaID\Entity\RefreshToken;
use Phprise\KoenmaID\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class TokenRefresher
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private TokenIssuer $issuer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function refresh(string $plainRefreshToken): IssuedToken
    {
        $refreshToken = $this->resolveUsableToken($plainRefreshToken);

        $refreshToken->revoke();
        $this->entityManager->flush();

        return $this->issuer->issue($refreshToken->getUser());
    }

    private function resolveUsableToken(string $plainRefreshToken): RefreshToken
    {
        $refreshToken = $this->refreshTokens->findOneByHash(hash('sha256', $plainRefreshToken));

        if (!$refreshToken instanceof RefreshToken || !$refreshToken->isUsable()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid refresh token.');
        }

        return $refreshToken;
    }
}
