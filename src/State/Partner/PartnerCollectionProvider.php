<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Partner;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\Partner\PartnerOutput;
use Phprise\KoenmaID\Repository\PartnerRepository;

/**
 * @implements ProviderInterface<PartnerOutput>
 */
final readonly class PartnerCollectionProvider implements ProviderInterface
{
    public function __construct(private PartnerRepository $partners)
    {
    }

    /**
     * @return list<PartnerOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $outputs = [];
        foreach ($this->partners->findBy(['deletedAt' => null]) as $partner) {
            $outputs[] = PartnerOutput::fromEntity($partner);
        }

        return $outputs;
    }
}
