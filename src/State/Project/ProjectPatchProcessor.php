<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Project\ProjectOutput;
use Phprise\KoenmaID\ApiResource\Project\ProjectPatchInput;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ProjectPatchInput, ProjectOutput>
 */
final readonly class ProjectPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private ProjectRepository $projects,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProjectOutput
    {
        if (!$data instanceof ProjectPatchInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $project = $this->projects->find($uriVariables['id'] ?? null);

        if (!$project instanceof Project || null !== $project->deletedAt()) {
            throw new NotFoundHttpException('Project not found.');
        }

        if (null !== $data->name) {
            $project->rename($data->name);
        }

        if (null !== $data->description) {
            $project->describe($data->description);
        }

        $this->entityManager->flush();

        return ProjectOutput::fromEntity($project);
    }
}
