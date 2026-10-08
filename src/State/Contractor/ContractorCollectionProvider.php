<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Phprise\KoenmaID\Service\Security\ScopedPartnerLookup;

/**
 * @implements ProviderInterface<Contractor>
 */
final readonly class ContractorCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ContractorRepository $contractors,
        private ScopedPartnerLookup $partners,
    ) {
    }

    /**
     * @return list<Contractor>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $partner = $this->partners->requireVisible($uriVariables['partnerId'] ?? null);

        return $this->contractors->findBy(['partner' => $partner, 'deletedAt' => null]);
    }
}
