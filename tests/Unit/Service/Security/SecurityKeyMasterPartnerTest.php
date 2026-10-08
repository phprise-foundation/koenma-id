<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Security;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Service\Security\MasterSecurityKey;
use Phprise\KoenmaID\Service\Security\SecurityKeyContext;
use Phprise\KoenmaID\Service\Security\SecurityKeyType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class SecurityKeyMasterPartnerTest extends TestCase
{
    private const string MASTER_KEY = 'sk_MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';
    private const string PARTNER_KEY = 'sk_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

    public function testMasterKeyResolvesToTheMasterScope(): void
    {
        $context = $this->context(self::MASTER_KEY);

        $scope = $context->scope();

        self::assertTrue($scope->isMaster());
        self::assertFalse($scope->isAnonymous());
        self::assertNull($scope->getPartner());
        self::assertSame(SecurityKeyType::Master, $context->keyType());
    }

    public function testPartnerKeyResolvesToThePartnerScope(): void
    {
        $partner = $this->createStub(Partner::class);

        $context = $this->context(self::PARTNER_KEY, $this->repositoryReturning($this->apiKey($partner)));

        $scope = $context->scope();

        self::assertFalse($scope->isMaster());
        self::assertFalse($scope->isAnonymous());
        self::assertSame($partner, $scope->getPartner());
        self::assertSame(SecurityKeyType::Partner, $context->keyType());
    }

    public function testMasterKeyIsNeverLookedUpAsAPartnerKey(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->never())
            ->method('findOneByHash');

        $context = $this->context(self::MASTER_KEY, $apiKeys);

        self::assertTrue($context->scope()->isMaster());
        self::assertSame(SecurityKeyType::Master, $context->keyType());
    }

    public function testPartnerKeyIsNeverResolvedAsTheMasterKey(): void
    {
        $partner = $this->createStub(Partner::class);

        $context = $this->context(self::PARTNER_KEY, $this->repositoryReturning($this->apiKey($partner)));

        self::assertFalse($context->scope()->isMaster());
        self::assertSame(SecurityKeyType::Partner, $context->keyType());
    }

    public function testMasterAndPartnerScopesAreMutuallyExclusive(): void
    {
        $partner = $this->createStub(Partner::class);

        $masterScope = $this->context(self::MASTER_KEY)->scope();
        $partnerScope = $this->context(
            self::PARTNER_KEY,
            $this->repositoryReturning($this->apiKey($partner)),
        )->scope();

        self::assertTrue($masterScope->isMaster());
        self::assertFalse($partnerScope->isMaster());

        self::assertNull($masterScope->getPartner());
        self::assertSame($partner, $partnerScope->getPartner());
        self::assertNotSame($masterScope, $partnerScope);
    }

    private function apiKey(Partner $partner): ApiKey
    {
        $project = $this->createStub(Project::class);
        $project->method('getPartner')->willReturn($partner);

        $apiKey = $this->createStub(ApiKey::class);
        $apiKey->method('getProject')->willReturn($project);
        $apiKey->method('getDeletedAt')->willReturn(null);
        $apiKey->method('isExpired')->willReturn(false);

        return $apiKey;
    }

    private function repositoryReturning(?ApiKey $apiKey): ApiKeyRepository
    {
        $repository = $this->createStub(ApiKeyRepository::class);
        $repository->method('findOneByHash')->willReturn($apiKey);

        return $repository;
    }

    private function context(?string $header, ?ApiKeyRepository $apiKeys = null): SecurityKeyContext
    {
        $stack = new RequestStack();
        $request = new Request();

        if (null !== $header) {
            $request->headers->set(SecurityKeyContext::HEADER, $header);
        }

        $stack->push($request);

        return new SecurityKeyContext(
            $stack,
            $apiKeys ?? $this->createStub(ApiKeyRepository::class),
            new MasterSecurityKey(self::MASTER_KEY),
        );
    }
}
