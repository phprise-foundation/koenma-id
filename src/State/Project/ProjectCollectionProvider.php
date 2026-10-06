<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Phprise\KoenmaID\Repository\ProjectRepository;

/**
 * @implements ProviderInterface<Project>
 */
final readonly class ProjectCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ProjectRepository $projects,
        private PartnerRepository $partners,
    ) {
    }

    /**
     * @return list<Project>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $partner = $this->partners->find($uriVariables['partnerId'] ?? null);

        if (!$partner instanceof Partner) {
            return [];
        }

        return $this->projects->findBy(['partner' => $partner, 'deletedAt' => null]);
    }
}
