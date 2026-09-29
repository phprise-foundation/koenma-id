<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\User\UserOutput;
use Phprise\KoenmaID\ApiResource\User\UserPatchInput;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<UserPatchInput, UserOutput>
 */
final readonly class UserPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        if (!$data instanceof UserPatchInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $user = $this->users->find($uriVariables['id'] ?? null);

        if (!$user instanceof User || null !== $user->deletedAt()) {
            throw new NotFoundHttpException('User not found.');
        }

        if (null !== $data->username) {
            $user->rename($data->username);
        }

        if (null !== $data->emailAddress) {
            $user->changeEmailAddress($data->emailAddress);
        }

        if (null !== $data->password) {
            $user->changePassword($this->passwordHasher->hashPassword($user, $data->password));
        }

        $this->entityManager->flush();

        return UserOutput::fromEntity($user);
    }
}
