<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Service\Security\ScopedProjectLookup;

/**
 * @implements ProviderInterface<ApiKey>
 */
final readonly class ApiKeyCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private ScopedProjectLookup $projects,
    ) {
    }

    /**
     * @return list<ApiKey>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $project = $this->projects->requireVisible($uriVariables['projectId'] ?? null);

        return $this->apiKeys->findActiveByProject($project);
    }
}
