<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Project\ProjectInput;
use Phprise\KoenmaID\ApiResource\Project\ProjectOutput;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Phprise\KoenmaID\Service\Project\ProjectRegistrar;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ProjectInput, ProjectOutput>
 */
final readonly class ProjectPostProcessor implements ProcessorInterface
{
    public function __construct(
        private PartnerRepository $partners,
        private ProjectRegistrar $registrar,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProjectOutput
    {
        if (!$data instanceof ProjectInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $partner = $this->partners->find($uriVariables['partnerId'] ?? null);

        if (!$partner instanceof Partner || null !== $partner->deletedAt()) {
            throw new NotFoundHttpException('Partner not found.');
        }

        $project = $this->registrar->register($partner, $data->name, $data->description);

        return ProjectOutput::fromEntity($project);
    }
}
