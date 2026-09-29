<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\Contractor\ContractorOutput;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ContractorOutput>
 */
final readonly class ContractorItemProvider implements ProviderInterface
{
    public function __construct(private ContractorRepository $contractors)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ContractorOutput
    {
        $contractor = $this->contractors->find($uriVariables['id'] ?? null);

        if (!$contractor instanceof Contractor || null !== $contractor->deletedAt()) {
            throw new NotFoundHttpException('Contractor not found.');
        }

        return ContractorOutput::fromEntity($contractor);
    }
}
