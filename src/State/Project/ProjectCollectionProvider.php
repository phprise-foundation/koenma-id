<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\Project\ProjectOutput;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Phprise\KoenmaID\Repository\ProjectRepository;

/**
 * @implements ProviderInterface<ProjectOutput>
 */
final readonly class ProjectCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ProjectRepository $projects,
        private PartnerRepository $partners,
    ) {
    }

    /**
     * @return list<ProjectOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $partner = $this->partners->find($uriVariables['partnerId'] ?? null);

        if (!$partner instanceof Partner) {
            return [];
        }

        $outputs = [];

        foreach ($this->projects->findBy(['partner' => $partner, 'deletedAt' => null]) as $project) {
            $outputs[] = ProjectOutput::fromEntity($project);
        }

        return $outputs;
    }
}
