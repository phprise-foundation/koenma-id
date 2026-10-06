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

final class SecurityKeyTypeTest extends TestCase
{
    private const string MASTER_KEY = 'sk_MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';
    private const string VALID_KEY = 'sk_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

    public function testResolvesAnonymousWhenHeaderIsAbsent(): void
    {
        self::assertSame(SecurityKeyType::Anonymous, $this->context(null)->keyType());
    }

    public function testResolvesMasterWhenMasterKeyIsUsed(): void
    {
        self::assertSame(SecurityKeyType::Master, $this->context(self::MASTER_KEY)->keyType());
    }

    public function testResolvesPartnerForValidApiKey(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn($this->apiKey());

        self::assertSame(SecurityKeyType::Partner, $this->context(self::VALID_KEY, $apiKeys)->keyType());
    }

    public function testResolvesAnonymousForUnknownKey(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn(null);

        self::assertSame(SecurityKeyType::Anonymous, $this->context(self::VALID_KEY, $apiKeys)->keyType());
    }

    public function testResolvesAnonymousForMalformedKey(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->never())
            ->method('findOneByHash');

        self::assertSame(SecurityKeyType::Anonymous, $this->context('not-a-key', $apiKeys)->keyType());
    }

    public function testResolvesAnonymousForDeletedApiKey(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn($this->apiKey(deletedAt: new \DateTimeImmutable()));

        self::assertSame(SecurityKeyType::Anonymous, $this->context(self::VALID_KEY, $apiKeys)->keyType());
    }

    public function testResolvesAnonymousForExpiredApiKey(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn($this->apiKey(expired: true));

        self::assertSame(SecurityKeyType::Anonymous, $this->context(self::VALID_KEY, $apiKeys)->keyType());
    }

    public function testDoesNotAlterResolvedScope(): void
    {
        $partner = $this->createStub(Partner::class);

        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn($this->apiKey($partner));

        $context = $this->context(self::VALID_KEY, $apiKeys);

        self::assertSame(SecurityKeyType::Partner, $context->keyType());
        self::assertFalse($context->scope()->isMaster());
        self::assertFalse($context->scope()->isAnonymous());
        self::assertSame($partner, $context->scope()->getPartner());
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
