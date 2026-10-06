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

    public function testAnonymousScope(): void
    {
        $scope = SecurityScope::anonymous();

        self::assertFalse($scope->isMaster());
        self::assertTrue($scope->isAnonymous());
        self::assertNull($scope->getPartner());
        self::assertNull($scope->partnerId());
    }
}
