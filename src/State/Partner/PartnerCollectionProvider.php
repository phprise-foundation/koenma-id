<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Partner;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;

/**
 * @implements ProviderInterface<Partner>
 */
final readonly class PartnerCollectionProvider implements ProviderInterface
{
    public function __construct(private PartnerRepository $partners)
    {
    }

    /**
     * @return list<Partner>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->partners->findBy(['deletedAt' => null]);
    }
}
