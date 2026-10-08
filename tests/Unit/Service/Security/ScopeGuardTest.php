<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Phprise\KoenmaID\Service\Security\SecurityScopeProvider;
use Phprise\KoenmaID\ValueObject\PartnerId;
use PHPUnit\Framework\TestCase;

final class ScopeGuardTest extends TestCase
{
    public function testScopedPartnerIsNullForTheMasterScope(): void
    {
        $guard = $this->guard(SecurityScope::master());

        self::assertNull($guard->scopedPartner());
    }

    public function testScopedPartnerIsNullForTheAnonymousScope(): void
    {
        $guard = $this->guard(SecurityScope::anonymous());

        self::assertNull($guard->scopedPartner());
    }

    public function testScopedPartnerIsTheScopePartnerForAPartnerScope(): void
    {
        $partner = $this->partner(PartnerId::generate());
        $guard = $this->guard(SecurityScope::partner($partner));

        self::assertSame($partner, $guard->scopedPartner());
    }

    public function testAllowsAnyPartnerForTheMasterScope(): void
    {
        $guard = $this->guard(SecurityScope::master());

        self::assertTrue($guard->allows($this->partner(PartnerId::generate())));
    }

    public function testAllowsAnyPartnerForTheAnonymousScope(): void
    {
        $guard = $this->guard(SecurityScope::anonymous());

        self::assertTrue($guard->allows($this->partner(PartnerId::generate())));
    }

    public function testAllowsTheOwnPartnerForAPartnerScope(): void
    {
        $partnerId = PartnerId::generate();
        $guard = $this->guard(SecurityScope::partner($this->partner($partnerId)));

        self::assertTrue($guard->allows($this->partner($partnerId)));
    }

    public function testDeniesAnotherPartnerForAPartnerScope(): void
    {
        $guard = $this->guard(SecurityScope::partner($this->partner(PartnerId::generate())));

        self::assertFalse($guard->allows($this->partner(PartnerId::generate())));
    }

    private function guard(SecurityScope $scope): ScopeGuard
    {
        $provider = $this->createStub(SecurityScopeProvider::class);
        $provider->method('scope')->willReturn($scope);

        return new ScopeGuard($provider);
    }

    private function partner(PartnerId $id): Partner
    {
        $partner = $this->createStub(Partner::class);
        $partner->method('getId')->willReturn($id);

        return $partner;
    }
}
