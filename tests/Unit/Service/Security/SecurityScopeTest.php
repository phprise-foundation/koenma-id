<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Phprise\KoenmaID\ValueObject\PartnerId;
use PHPUnit\Framework\TestCase;

final class SecurityScopeTest extends TestCase
{
    public function testMasterScope(): void
    {
        $scope = SecurityScope::master();

        self::assertTrue($scope->isMaster());
        self::assertFalse($scope->isAnonymous());
        self::assertNull($scope->getPartner());
        self::assertNull($scope->partnerId());
    }

    public function testPartnerScopeExposesPartner(): void
    {
        $partnerId = PartnerId::generate();
        $partner = $this->createStub(Partner::class);
        $partner->method('getId')->willReturn($partnerId);

        $scope = SecurityScope::partner($partner);

        self::assertFalse($scope->isMaster());
        self::assertFalse($scope->isAnonymous());
        self::assertSame($partner, $scope->getPartner());
        self::assertSame($partnerId, $scope->partnerId());
    }

    public function testPartnerIdIsDerivedFromTheExposedPartner(): void
    {
        $partnerId = PartnerId::generate();
        $partner = $this->createMock(Partner::class);
        $partner->expects($this->once())
            ->method('getId')
            ->willReturn($partnerId);

        $scope = SecurityScope::partner($partner);

        $resolvedPartnerId = $scope->partnerId();

        self::assertNotNull($resolvedPartnerId);
        self::assertTrue($resolvedPartnerId->equals($partnerId));
    }

    public function testDistinctPartnerScopesExposeTheirOwnPartner(): void
    {
        $firstId = PartnerId::generate();
        $first = $this->createStub(Partner::class);
        $first->method('getId')->willReturn($firstId);

        $secondId = PartnerId::generate();
        $second = $this->createStub(Partner::class);
        $second->method('getId')->willReturn($secondId);

        $firstScope = SecurityScope::partner($first);
        $secondScope = SecurityScope::partner($second);

        self::assertSame($first, $firstScope->getPartner());
        self::assertSame($second, $secondScope->getPartner());
        self::assertNotSame($firstScope->getPartner(), $secondScope->getPartner());
        self::assertFalse($firstScope->partnerId()?->equals($secondId));
        self::assertFalse($secondScope->partnerId()?->equals($firstId));
    }

    public function testAnonymousScope(): void
    {
        $scope = SecurityScope::anonymous();

        self::assertFalse($scope->isMaster());
        self::assertTrue($scope->isAnonymous());
        self::assertNull($scope->getPartner());
        self::assertNull($scope->partnerId());
    }
}
