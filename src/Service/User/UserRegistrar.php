<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\User;

use Phprise\KoenmaID\ApiResource\User\UserInput;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Phprise\KoenmaID\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserRegistrar
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private ContractorRepository $contractors,
        private UserRepository $users,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function register(UserInput $input): User
    {
        $apiKey = $this->resolveApiKey($input->apiKey);
        $this->assertUsernameIsAvailable($input->username);

        $contractor = $this->resolveContractor($apiKey, $input->contractorName, $input->contractorDocument);

        $user = new User($contractor, $input->username, $input->emailAddress);
        $user->changePassword($this->passwordHasher->hashPassword($user, $input->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function resolveApiKey(string $plainKey): ApiKey
    {
        $apiKey = $this->apiKeys->findOneByHash(hash('sha256', $plainKey));

        if (!$apiKey instanceof ApiKey || null !== $apiKey->deletedAt() || !$apiKey->isExpired() === false) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid API key.');
        }

        return $apiKey;
    }

    private function resolveContractor(ApiKey $apiKey, string $name, string $document): Contractor
    {
        $existing = $this->contractors->findOneBy(['document' => $document]);
        if ($existing instanceof Contractor) {
            return $existing;
        }

        $contractor = new Contractor($apiKey->project()->partner(), $name, $document);
        $this->entityManager->persist($contractor);

        return $contractor;
    }

    private function assertUsernameIsAvailable(string $username): void
    {
        if (null !== $this->users->findOneByUsername($username)) {
            throw new ConflictHttpException('Username already registered.');
        }
    }
}
