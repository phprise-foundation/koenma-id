<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Service\Security\ScopedContractorLookup;

/**
 * @implements ProviderInterface<Contractor>
 */
final readonly class ContractorItemProvider implements ProviderInterface
{
    public function __construct(private ScopedContractorLookup $contractors)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Contractor
    {
        return $this->contractors->requireVisible($uriVariables['id'] ?? null);
    }
}
