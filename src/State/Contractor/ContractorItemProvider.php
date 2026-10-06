<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Contractor>
 */
final readonly class ContractorItemProvider implements ProviderInterface
{
    public function __construct(private ContractorRepository $contractors)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Contractor
    {
        $contractor = $this->contractors->find($uriVariables['id'] ?? null);

        if (!$contractor instanceof Contractor || null !== $contractor->getDeletedAt()) {
            throw new NotFoundHttpException('Contractor not found.');
        }

        return $contractor;
    }
}
