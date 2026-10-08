<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Phprise\KoenmaID\Service\Security\ScopedPartnerLookup;

/**
 * @implements ProviderInterface<Project>
 */
final readonly class ProjectCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ProjectRepository $projects,
        private ScopedPartnerLookup $partners,
    ) {
    }

    /**
     * @return list<Project>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $partner = $this->partners->requireVisible($uriVariables['partnerId'] ?? null);

        return $this->projects->findBy(['partner' => $partner, 'deletedAt' => null]);
    }
}
