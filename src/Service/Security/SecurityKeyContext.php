<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\ValueObject\SecurityKey;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsAlias(SecurityScopeProvider::class)]
final class SecurityKeyContext implements SecurityScopeProvider
{
    public const string HEADER = 'X-Security-Key';

    private ?Request $cachedRequest = null;
    private ?SecurityScope $cachedScope = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ApiKeyRepository $apiKeys,
        private readonly MasterSecurityKey $masterKey,
    ) {
    }

    public function scope(): SecurityScope
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === $this->cachedRequest && null !== $this->cachedScope) {
            return $this->cachedScope;
        }

        $this->cachedRequest = $request;
        $this->cachedScope = $this->resolve($request);

        return $this->cachedScope;
    }

    public function keyType(): SecurityKeyType
    {
        $scope = $this->scope();

        if ($scope->isMaster()) {
            return SecurityKeyType::Master;
        }

        if (null !== $scope->getPartner()) {
            return SecurityKeyType::Partner;
        }

        return SecurityKeyType::Anonymous;
    }

    private function resolve(?Request $request): SecurityScope
    {
        $header = $this->headerValue($request);

        if (null === $header) {
            return SecurityScope::anonymous();
        }

        if ($this->masterKey->matches($header)) {
            return SecurityScope::master();
        }

        return $this->resolvePartnerScope($header);
    }

    private function resolvePartnerScope(string $header): SecurityScope
    {
        $apiKey = $this->findValidApiKey($header);

        if (!$apiKey instanceof ApiKey) {
            return SecurityScope::anonymous();
        }

        return SecurityScope::partner($apiKey->getProject()->getPartner());
    }

    private function findValidApiKey(string $header): ?ApiKey
    {
        try {
            $securityKey = SecurityKey::fromString($header);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $apiKey = $this->apiKeys->findOneByHash($securityKey->hash());

        if (!$apiKey instanceof ApiKey) {
            return null;
        }

        if (null !== $apiKey->getDeletedAt()) {
            return null;
        }

        if ($apiKey->isExpired()) {
            return null;
        }

        return $apiKey;
    }

    private function headerValue(?Request $request): ?string
    {
        if (null === $request) {
            return null;
        }

        $value = $request->headers->get(self::HEADER);

        if (null === $value || '' === $value) {
            return null;
        }

        return $value;
    }
}
