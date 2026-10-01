<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\ApiKey\ApiKeyOutput;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Repository\ProjectRepository;

/**
 * @implements ProviderInterface<ApiKeyOutput>
 */
final readonly class ApiKeyCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private ProjectRepository $projects,
    ) {
    }

    /**
     * @return list<ApiKeyOutput>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $project = $this->projects->find($uriVariables['projectId'] ?? null);

        if (!$project instanceof Project) {
            return [];
        }

        $outputs = [];

        foreach ($this->apiKeys->findActiveByProject($project) as $apiKey) {
            $outputs[] = ApiKeyOutput::fromEntity($apiKey);
        }

        return $outputs;
    }
}
