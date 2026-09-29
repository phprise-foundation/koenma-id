<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

use Phprise\KoenmaID\Entity\RefreshToken;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class TokenIssuer
{
    private const int REFRESH_TOKEN_TTL_DAYS = 30;

    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenRepository $refreshTokens,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function issue(User $user): IssuedToken
    {
        $accessToken = $this->jwtManager->create($user);
        $plainRefreshToken = $this->generatePlainRefreshToken();

        $refreshToken = new RefreshToken(
            $user,
            $this->hash($plainRefreshToken),
            new \DateTimeImmutable(\sprintf('+%d days', self::REFRESH_TOKEN_TTL_DAYS)),
        );

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return new IssuedToken($accessToken, $plainRefreshToken);
    }

    private function generatePlainRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
