<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Phprise\KoenmaID\Service\Security\ScopedPartnerLookup;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Phprise\KoenmaID\Service\Security\SecurityScopeProvider;
use Phprise\KoenmaID\ValueObject\PartnerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ScopedPartnerLookupTest extends TestCase
{
    public function testVisibleReturnsEveryPartnerWhenTheScopeIsUnrestricted(): void
    {
        $first = $this->partner(PartnerId::generate());
        $second = $this->partner(PartnerId::generate());

        $repository = $this->createMock(PartnerRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['deletedAt' => null])
            ->willReturn([$first, $second]);

        $lookup = new ScopedPartnerLookup($repository, $this->guard(SecurityScope::master()));

        self::assertSame([$first, $second], $lookup->visible());
    }

    public function testVisibleReturnsOnlyTheScopedPartnerForAPartnerScope(): void
    {
        $partner = $this->partner(PartnerId::generate());

        $repository = $this->createMock(PartnerRepository::class);
        $repository->expects(self::never())->method('findBy');

        $lookup = new ScopedPartnerLookup($repository, $this->guard(SecurityScope::partner($partner)));

        self::assertSame([$partner], $lookup->visible());
    }

    public function testRequireVisibleReturnsAPartnerOfTheScope(): void
    {
        $id = PartnerId::generate();
        $partner = $this->partner($id);

        $repository = $this->createStub(PartnerRepository::class);
        $repository->method('find')->willReturn($partner);

        $lookup = new ScopedPartnerLookup($repository, $this->guard(SecurityScope::partner($partner)));

        self::assertSame($partner, $lookup->requireVisible($id));
    }

    public function testRequireVisibleHidesAPartnerOfAnotherScope(): void
    {
        $repository = $this->createStub(PartnerRepository::class);
        $repository->method('find')->willReturn($this->partner(PartnerId::generate()));

        $lookup = new ScopedPartnerLookup(
            $repository,
            $this->guard(SecurityScope::partner($this->partner(PartnerId::generate()))),
        );

        $this->expectException(NotFoundHttpException::class);

        $lookup->requireVisible(PartnerId::generate());
    }

    public function testRequireVisibleThrowsWhenThePartnerDoesNotExist(): void
    {
        $repository = $this->createStub(PartnerRepository::class);
        $repository->method('find')->willReturn(null);

        $lookup = new ScopedPartnerLookup($repository, $this->guard(SecurityScope::master()));

        $this->expectException(NotFoundHttpException::class);

        $lookup->requireVisible(PartnerId::generate());
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
