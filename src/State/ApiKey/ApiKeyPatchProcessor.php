<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ApiKey, ApiKey>
 */
final readonly class ApiKeyPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScopeGuard $scopeGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ApiKey
    {
        if (!$data instanceof ApiKey) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $project = $data->getProject();

        if (!$project instanceof Project || !$this->scopeGuard->allows($project->getPartner())) {
            throw new NotFoundHttpException('ApiKey not found.');
        }

        $this->entityManager->flush();

        return $data;
    }
}
