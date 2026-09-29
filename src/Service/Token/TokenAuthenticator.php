<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class TokenAuthenticator
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function authenticate(string $plainApiKey, string $username, string $password): User
    {
        $apiKey = $this->resolveApiKey($plainApiKey);
        $user = $this->resolveUser($username);

        $this->assertUserBelongsToApiKeyPartner($user, $apiKey);
        $this->assertPasswordMatches($user, $password);

        return $user;
    }

    private function resolveApiKey(string $plainApiKey): ApiKey
    {
        $apiKey = $this->apiKeys->findOneByHash(hash('sha256', $plainApiKey));

        if (!$apiKey instanceof ApiKey || null !== $apiKey->deletedAt() || $apiKey->isExpired()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid API key.');
        }

        return $apiKey;
    }

    private function resolveUser(string $username): User
    {
        $user = $this->users->findOneByUsername($username);

        if (!$user instanceof User || null !== $user->deletedAt() || !$user->active()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }

        return $user;
    }

    private function assertUserBelongsToApiKeyPartner(User $user, ApiKey $apiKey): void
    {
        $userPartnerId = (string) $user->contractor()->partner()->id();
        $apiKeyPartnerId = (string) $apiKey->project()->partner()->id();

        if ($userPartnerId !== $apiKeyPartnerId) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }
    }

    private function assertPasswordMatches(User $user, string $password): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }
    }
}
