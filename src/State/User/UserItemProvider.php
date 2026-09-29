<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\User\UserOutput;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<UserOutput>
 */
final readonly class UserItemProvider implements ProviderInterface
{
    public function __construct(private UserRepository $users)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        $user = $this->users->find($uriVariables['id'] ?? null);

        if (!$user instanceof User || null !== $user->deletedAt()) {
            throw new NotFoundHttpException('User not found.');
        }

        return UserOutput::fromEntity($user);
    }
}
