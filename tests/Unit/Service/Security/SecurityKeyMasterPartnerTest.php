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

    public function testMasterKeyReturnsMasterKeyType(): void
    {
        $context = $this->context(self::MASTER_KEY);
        
        self::assertSame(SecurityKeyType::Master, $context->keyType());
    }

    public function testPartnerKeyReturnsPartnerKeyType(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn($this->apiKey());

        $context = $this->context(self::PARTNER_KEY, $apiKeys);
        
        self::assertSame(SecurityKeyType::Partner, $context->keyType());
    }

    public function testAnonymousKeyReturnsAnonymousKeyType(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn(null);

        $context = $this->context(self::PARTNER_KEY, $apiKeys);
        
        self::assertSame(SecurityKeyType::Anonymous, $context->keyType());
    }

    public function testMasterScopeIsDistinctFromPartnerScope(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn(null);

        $masterContext = $this->context(self::MASTER_KEY);
        $partnerContext = $this->context(self::PARTNER_KEY, $apiKeys);

        $masterScope = $masterContext->scope();
        $partnerScope = $partnerContext->scope();

        self::assertTrue($masterScope->isMaster());
        self::assertFalse($partnerScope->isMaster());
        
        self::assertFalse($masterScope->isAnonymous());
        self::assertTrue($partnerScope->isAnonymous());
        
        self::assertNull($masterScope->getPartner());
        self::assertNull($partnerScope->getPartner());
    }

    public function testPartnerScopeContainsPartnerInformation(): void
    {
        $partner = $this->createStub(Partner::class);
        $project = $this->createStub(Project::class);
        $project->method('getPartner')->willReturn($partner);

        $apiKey = $this->createStub(ApiKey::class);
        $apiKey->method('getProject')->willReturn($project);
        $apiKey->method('getDeletedAt')->willReturn(null);
        $apiKey->method('isExpired')->willReturn(false);

        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn($apiKey);

        $context = $this->context(self::PARTNER_KEY, $apiKeys);
        $scope = $context->scope();

        self::assertFalse($scope->isMaster());
        self::assertFalse($scope->isAnonymous());
        self::assertSame($partner, $scope->getPartner());
    }

    private function apiKey(
        ?Partner $partner = null,
        ?\DateTimeImmutable $deletedAt = null,
        bool $expired = false,
    ): ApiKey {
        $project = $this->createStub(Project::class);
        $project->method('getPartner')->willReturn($partner ?? $this->createStub(Partner::class));

        $apiKey = $this->createStub(ApiKey::class);
        $apiKey->method('getProject')->willReturn($project);
        $apiKey->method('getDeletedAt')->willReturn($deletedAt);
        $apiKey->method('isExpired')->willReturn($expired);

        return $apiKey;
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