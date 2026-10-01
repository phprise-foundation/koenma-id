<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\User;

use Phprise\KoenmaID\ApiResource\User\UserInput;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\UserRepository;
use Phprise\KoenmaID\Service\Security\SecurityKeyContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserRegistrar
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private SecurityKeyContext $securityKeyContext,
    ) {
    }

    public function register(Contractor $contractor, UserInput $input): User
    {
        $this->assertSecurityKeyBelongsToContractor($contractor);
        $this->assertUsernameIsAvailable($contractor, $input->username);

        $user = new User($contractor, $input->username, $input->emailAddress);
        $user->changePassword($this->passwordHasher->hashPassword($user, $input->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function assertSecurityKeyBelongsToContractor(Contractor $contractor): void
    {
        $scope = $this->securityKeyContext->scope();

        if ($scope->isMaster()) {
            return;
        }

        $partnerId = $contractor->partner()->id();

        if (null === $scope->partnerId() || !$scope->partnerId()->equals($partnerId)) {
            throw new UnauthorizedHttpException('SecurityKey', 'Invalid security key for this contractor.');
        }
    }

    private function assertUsernameIsAvailable(Contractor $contractor, string $username): void
    {
        if (null !== $this->users->findOneByContractorAndUsername($contractor, $username)) {
            throw new ConflictHttpException('Username already registered for this contractor.');
        }
    }
}
