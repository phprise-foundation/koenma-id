<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Factory;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class TestDataFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function createPartner(string $suffix = '1'): Partner
    {
        $partner = new Partner(
            'Partner '.$suffix,
            'partner'.$suffix.'@example.com',
            'doc-partner-'.$suffix,
        );

        $this->entityManager->persist($partner);
        $this->entityManager->flush();

        return $partner;
    }

    public function createProject(Partner $partner, string $suffix = '1'): Project
    {
        $project = new Project($partner, 'Project '.$suffix);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    public function createApiKey(Project $project, string $plainKey, string $suffix = '1'): ApiKey
    {
        $apiKey = new ApiKey(
            $project,
            'ApiKey '.$suffix,
            hash('sha256', $plainKey),
            substr($plainKey, 0, 8),
            substr($plainKey, -8),
        );

        $this->entityManager->persist($apiKey);
        $this->entityManager->flush();

        return $apiKey;
    }

    public function createContractor(Partner $partner, string $suffix = '1'): Contractor
    {
        $contractor = new Contractor($partner, 'Contractor '.$suffix, 'doc-contractor-'.$suffix);

        $this->entityManager->persist($contractor);
        $this->entityManager->flush();

        return $contractor;
    }

    public function createUser(Contractor $contractor, string $plainPassword, string $suffix = '1'): User
    {
        $user = new User($contractor, 'user'.$suffix, 'user'.$suffix.'@example.com');
        $user->changePassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
