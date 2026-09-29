<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\Project\ProjectOutput;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ProjectOutput>
 */
final readonly class ProjectItemProvider implements ProviderInterface
{
    public function __construct(private ProjectRepository $projects)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProjectOutput
    {
        $project = $this->projects->find($uriVariables['id'] ?? null);

        if (!$project instanceof Project || null !== $project->deletedAt()) {
            throw new NotFoundHttpException('Project not found.');
        }

        return ProjectOutput::fromEntity($project);
    }
}
