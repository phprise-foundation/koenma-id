<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\Contractor\ContractorOutput;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Phprise\KoenmaID\Repository\PartnerRepository;

/**
 * @implements ProviderInterface<ContractorOutput>
 */
final readonly class ContractorCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ContractorRepository $contractors,
        private PartnerRepository $partners,
    ) {
    }

    /**
     * @return list<ContractorOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $partner = $this->partners->find($uriVariables['partnerId'] ?? null);

        if (!$partner instanceof Partner) {
            return [];
        }

        $outputs = [];

        foreach ($this->contractors->findBy(['partner' => $partner, 'deletedAt' => null]) as $contractor) {
            $outputs[] = ContractorOutput::fromEntity($contractor);
        }

        return $outputs;
    }
}
