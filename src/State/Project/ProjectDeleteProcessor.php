<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<Project, null>
 */
final readonly class ProjectDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScopeGuard $scopeGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$data instanceof Project) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $partner = $data->getPartner();

        if (!$partner instanceof Partner) {
            throw new NotFoundHttpException('Project not found.');
        }

        $this->scopeGuard->assertCanWrite($partner, 'Project not found.');

        $data->delete();
        $this->entityManager->flush();

        return null;
    }
}
