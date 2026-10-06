<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ApiKey>
 */
final readonly class CreateProvider implements ProviderInterface
{
    public function __construct(private ProjectRepository $projects)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ApiKey
    {
        $project = $this->projects->find($uriVariables['projectId'] ?? null);

        if (!$project instanceof Project || null !== $project->getDeletedAt()) {
            throw new NotFoundHttpException('Project not found.');
        }

        return (new ApiKey())->setProject($project);
    }
}
