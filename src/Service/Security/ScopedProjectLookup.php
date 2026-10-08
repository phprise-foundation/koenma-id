<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ScopedProjectLookup
{
    public function __construct(
        private ProjectRepository $projects,
        private ScopeGuard $guard,
    ) {
    }

    public function requireVisible(mixed $id): Project
    {
        $project = $this->projects->find($id);

        if (!$project instanceof Project || null !== $project->getDeletedAt()) {
            throw new NotFoundHttpException('Project not found.');
        }

        if (!$this->guard->allows($project->getPartner())) {
            throw new NotFoundHttpException('Project not found.');
        }

        return $project;
    }
}
