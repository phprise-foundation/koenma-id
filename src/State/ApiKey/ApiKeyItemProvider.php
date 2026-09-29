<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\ApiKey\ApiKeyOutput;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ApiKeyOutput>
 */
final readonly class ApiKeyItemProvider implements ProviderInterface
{
    public function __construct(private ApiKeyRepository $apiKeys)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ApiKeyOutput
    {
        $apiKey = $this->apiKeys->find($uriVariables['id'] ?? null);

        if (!$apiKey instanceof ApiKey || null !== $apiKey->deletedAt()) {
            throw new NotFoundHttpException('ApiKey not found.');
        }

        return ApiKeyOutput::fromEntity($apiKey);
    }
}
