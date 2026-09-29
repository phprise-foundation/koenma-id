<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\ApiKey\ApiKeyInput;
use Phprise\KoenmaID\ApiResource\ApiKey\ApiKeyOutput;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Phprise\KoenmaID\Service\ApiKey\ApiKeyIssuer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ApiKeyInput, ApiKeyOutput>
 */
final readonly class ApiKeyPostProcessor implements ProcessorInterface
{
    public function __construct(
        private ProjectRepository $projects,
        private ApiKeyIssuer $issuer,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ApiKeyOutput
    {
        if (!$data instanceof ApiKeyInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $project = $this->projects->find($uriVariables['projectId'] ?? null);

        if (!$project instanceof Project || null !== $project->deletedAt()) {
            throw new NotFoundHttpException('Project not found.');
        }

        $issued = $this->issuer->issue($project, $data->name, $data->expiresInDays);

        return ApiKeyOutput::fromIssued($issued->apiKey, $issued->plainKey);
    }
}
