<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<User>
 */
final readonly class CreateProvider implements ProviderInterface
{
    public function __construct(private ContractorRepository $contractors)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $contractor = $this->contractors->find($uriVariables['contractorId'] ?? null);

        if (!$contractor instanceof Contractor || null !== $contractor->getDeletedAt()) {
            throw new NotFoundHttpException('Contractor not found.');
        }

        return (new User())->setContractor($contractor);
    }
}