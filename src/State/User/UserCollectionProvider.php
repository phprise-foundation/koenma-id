<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\UserRepository;
use Phprise\KoenmaID\Service\Security\ScopedContractorLookup;

/**
 * @implements ProviderInterface<User>
 */
final readonly class UserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $users,
        private ScopedContractorLookup $contractors,
    ) {
    }

    /**
     * @return list<User>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $contractor = $this->contractors->requireVisible($uriVariables['contractorId'] ?? null);

        return $this->users->findBy(['contractor' => $contractor, 'deletedAt' => null]);
    }
}
