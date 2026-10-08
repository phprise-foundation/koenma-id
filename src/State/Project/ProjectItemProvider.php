<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Phprise\KoenmaID\Service\Security\ScopedProjectLookup;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Project>
 */
final readonly class ProjectItemProvider implements ProviderInterface
{
    public function __construct(
        private ProjectRepository $projects,
        private ScopedProjectLookup $projectsLookup,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Project
    {
        return $this->projectsLookup->requireVisible($uriVariables['id'] ?? null);
    }
}
