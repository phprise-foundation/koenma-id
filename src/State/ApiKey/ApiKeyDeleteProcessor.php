<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<null, null>
 */
final readonly class ApiKeyDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private EntityManagerInterface $entityManager,
        private ScopeGuard $scopeGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $apiKey = $this->resolveApiKey($uriVariables['id'] ?? null);
        $this->assertScopeOwnsApiKey($apiKey);

        $apiKey->delete();
        $this->entityManager->flush();

        return null;
    }

    private function resolveApiKey(mixed $id): ApiKey
    {
        $apiKey = $this->apiKeys->find($id);

        if (!$apiKey instanceof ApiKey || null !== $apiKey->getDeletedAt()) {
            throw new NotFoundHttpException('ApiKey not found.');
        }

        return $apiKey;
    }

    private function assertScopeOwnsApiKey(ApiKey $apiKey): void
    {
        $partner = $apiKey->getProject()?->getPartner();

        if (!$partner instanceof Partner) {
            throw new NotFoundHttpException('ApiKey not found.');
        }

        $this->scopeGuard->assertCanWrite($partner, 'ApiKey not found.');
    }
}
