<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Project>
 */
final readonly class ProjectItemProvider implements ProviderInterface
{
    public function __construct(private ProjectRepository $projects)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Project
    {
        $project = $this->projects->find($uriVariables['id'] ?? null);

        if (!$project instanceof Project || null !== $project->getDeletedAt()) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $project;
    }
}
