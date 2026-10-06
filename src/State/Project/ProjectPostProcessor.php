<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Service\Project\ProjectRegistrar;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<Project, Project>
 */
final readonly class ProjectPostProcessor implements ProcessorInterface
{
    public function __construct(private ProjectRegistrar $registrar)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Project
    {
        if (!$data instanceof Project) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        return $this->registrar->register($data);
    }
}
