<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

use Phprise\KoenmaID\Entity\RefreshToken;
use Phprise\KoenmaID\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class TokenRevoker
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function revoke(string $plainRefreshToken): void
    {
        $refreshToken = $this->refreshTokens->findOneByHash(hash('sha256', $plainRefreshToken));

        if (!$refreshToken instanceof RefreshToken) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid refresh token.');
        }

        $refreshToken->revoke();
        $this->entityManager->flush();
    }
}
