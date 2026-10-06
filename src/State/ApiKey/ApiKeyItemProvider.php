<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ApiKey>
 */
final readonly class ApiKeyItemProvider implements ProviderInterface
{
    public function __construct(private ApiKeyRepository $apiKeys)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ApiKey
    {
        $apiKey = $this->apiKeys->find($uriVariables['id'] ?? null);

        if (!$apiKey instanceof ApiKey || null !== $apiKey->getDeletedAt()) {
            throw new NotFoundHttpException('ApiKey not found.');
        }

        return $apiKey;
    }
}
