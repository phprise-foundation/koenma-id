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
use Phprise\KoenmaID\ValueObject\PartnerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class SecurityKeyContextPartnerTest extends TestCase
{
    private const string MASTER_KEY = 'sk_MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';
    private const string PARTNER_KEY = 'sk_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
    private const string OTHER_PARTNER_KEY = 'sk_BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';

    public function testContextExposesThePartnerResolvedFromTheApiKey(): void
    {
        $partnerId = PartnerId::generate();
        $partner = $this->partner($partnerId);

        $context = $this->context(self::PARTNER_KEY, $this->repositoryWith($this->apiKey($partner)));

        $scope = $context->scope();

        self::assertFalse($scope->isMaster());
        self::assertFalse($scope->isAnonymous());
        self::assertSame($partner, $scope->getPartner());
        self::assertSame($partnerId, $scope->partnerId());
        self::assertSame(SecurityKeyType::Partner, $context->keyType());
    }

    public function testExposedPartnerCarriesTheDataOfTheAssociatedPartner(): void
    {
        $partner = $this->partner(PartnerId::generate(), 'Acme Inc.');

        $context = $this->context(self::PARTNER_KEY, $this->repositoryWith($this->apiKey($partner)));

        $exposed = $context->scope()->getPartner();

        self::assertInstanceOf(Partner::class, $exposed);
        self::assertSame('Acme Inc.', $exposed->getName());
    }

    public function testContextDoesNotExposeAPartnerForTheMasterKey(): void
    {
        $context = $this->context(self::MASTER_KEY);

        self::assertNull($context->scope()->getPartner());
        self::assertSame(SecurityKeyType::Master, $context->keyType());
    }

    public function testContextDoesNotExposeAPartnerWhenTheHeaderIsAbsent(): void
    {
        $context = $this->context(null);

        self::assertNull($context->scope()->getPartner());
        self::assertSame(SecurityKeyType::Anonymous, $context->keyType());
    }

    public function testContextDoesNotExposeAPartnerForAnUnknownKey(): void
    {
        $context = $this->context(self::PARTNER_KEY, $this->repositoryReturning(null));

        self::assertNull($context->scope()->getPartner());
        self::assertSame(SecurityKeyType::Anonymous, $context->keyType());
    }

    public function testContextDoesNotExposeAPartnerForADeletedApiKey(): void
    {
        $apiKey = $this->apiKey($this->partner(PartnerId::generate()), deletedAt: new \DateTimeImmutable());

        $context = $this->context(self::PARTNER_KEY, $this->repositoryWith($apiKey));

        self::assertNull($context->scope()->getPartner());
        self::assertSame(SecurityKeyType::Anonymous, $context->keyType());
    }

    public function testContextDoesNotExposeAPartnerForAnExpiredApiKey(): void
    {
        $apiKey = $this->apiKey($this->partner(PartnerId::generate()), expired: true);

        $context = $this->context(self::PARTNER_KEY, $this->repositoryWith($apiKey));

        self::assertNull($context->scope()->getPartner());
        self::assertSame(SecurityKeyType::Anonymous, $context->keyType());
    }

    public function testResolvedScopeIsCachedAndKeepsExposingTheSamePartner(): void
    {
        $partner = $this->partner(PartnerId::generate());

        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn($this->apiKey($partner));

        $context = $this->context(self::PARTNER_KEY, $apiKeys);

        $first = $context->scope();
        $second = $context->scope();

        self::assertSame($first, $second);
        self::assertSame($partner, $first->getPartner());
        self::assertSame($partner, $second->getPartner());
    }

    public function testDistinctContextsExposeTheirOwnPartner(): void
    {
        $first = $this->partner(PartnerId::generate(), 'First');
        $second = $this->partner(PartnerId::generate(), 'Second');

        $firstContext = $this->context(self::PARTNER_KEY, $this->repositoryWith($this->apiKey($first)));
        $secondContext = $this->context(self::OTHER_PARTNER_KEY, $this->repositoryWith($this->apiKey($second)));

        self::assertSame($first, $firstContext->scope()->getPartner());
        self::assertSame($second, $secondContext->scope()->getPartner());
        self::assertNotSame($firstContext->scope()->getPartner(), $secondContext->scope()->getPartner());
    }

    private function partner(PartnerId $id, string $name = 'Partner'): Partner
    {
        $partner = $this->createStub(Partner::class);
        $partner->method('getId')->willReturn($id);
        $partner->method('getName')->willReturn($name);

        return $partner;
    }

    private function apiKey(
        Partner $partner,
        ?\DateTimeImmutable $deletedAt = null,
        bool $expired = false,
    ): ApiKey {
        $project = $this->createStub(Project::class);
        $project->method('getPartner')->willReturn($partner);

        $apiKey = $this->createStub(ApiKey::class);
        $apiKey->method('getProject')->willReturn($project);
        $apiKey->method('getDeletedAt')->willReturn($deletedAt);
        $apiKey->method('isExpired')->willReturn($expired);

        return $apiKey;
    }

    private function repositoryWith(ApiKey $apiKey): ApiKeyRepository
    {
        return $this->repositoryReturning($apiKey);
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
