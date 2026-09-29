<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\ApiKey\ApiKeyOutput;
use Phprise\KoenmaID\ApiResource\ApiKey\ApiKeyPatchInput;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ApiKeyPatchInput, ApiKeyOutput>
 */
final readonly class ApiKeyPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ApiKeyOutput
    {
        if (!$data instanceof ApiKeyPatchInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $apiKey = $this->apiKeys->find($uriVariables['id'] ?? null);

        if (!$apiKey instanceof ApiKey || null !== $apiKey->deletedAt()) {
            throw new NotFoundHttpException('ApiKey not found.');
        }

        if (null !== $data->name) {
            $apiKey->rename($data->name);
        }

        $this->entityManager->flush();

        return ApiKeyOutput::fromEntity($apiKey);
    }
}
