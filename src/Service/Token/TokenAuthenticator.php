<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\UserRepository;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class TokenAuthenticator
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function authenticate(SecurityScope $scope, string $username, string $password): User
    {
        $user = $this->resolveUser($username);

        $this->assertUserBelongsToScope($user, $scope);
        $this->assertPasswordMatches($user, $password);

        return $user;
    }

    private function resolveUser(string $username): User
    {
        $user = $this->users->findOneByUsername($username);

        if (!$user instanceof User || null !== $user->deletedAt() || !$user->active()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
        }

        return $user;
    }

    private function assertUserBelongsToScope(User $user, SecurityScope $scope): void
    {
        if ($scope->isMaster()) {
            return;
        }

        $userPartnerId = $user->contractor()->partner()->id();

        if (null === $scope->partnerId() || !$scope->partnerId()->equals($userPartnerId)) {
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
