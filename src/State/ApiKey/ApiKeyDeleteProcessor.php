<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Service\Security\SecurityKeyContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<null, null>
 */
final readonly class ApiKeyDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private ApiKeyRepository $apiKeys,
        private EntityManagerInterface $entityManager,
        private SecurityKeyContext $securityKeyContext,
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
        $scope = $this->securityKeyContext->scope();

        if ($scope->isMaster()) {
            return;
        }

        $partnerId = $apiKey->getProject()->getPartner()->getId();

        if (null === $scope->partnerId() || !$scope->partnerId()->equals($partnerId)) {
            throw new UnauthorizedHttpException('SecurityKey', 'Invalid security key for this API key.');
        }
    }
}
