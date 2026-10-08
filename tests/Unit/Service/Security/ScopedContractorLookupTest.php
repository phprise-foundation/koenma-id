<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Security;

use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Phprise\KoenmaID\Service\Security\ScopedContractorLookup;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Phprise\KoenmaID\Service\Security\SecurityScopeProvider;
use Phprise\KoenmaID\ValueObject\PartnerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ScopedContractorLookupTest extends TestCase
{
    public function testRequireVisibleReturnsAContractorOfTheUnrestrictedScope(): void
    {
        $contractor = $this->contractor($this->partner(PartnerId::generate()));

        $lookup = new ScopedContractorLookup($this->contractorsReturning($contractor), $this->guard(SecurityScope::master()));

        self::assertSame($contractor, $lookup->requireVisible(PartnerId::generate()));
    }

    public function testRequireVisibleReturnsAContractorOfTheScopedPartner(): void
    {
        $partnerId = PartnerId::generate();
        $contractor = $this->contractor($this->partner($partnerId));

        $lookup = new ScopedContractorLookup(
            $this->contractorsReturning($contractor),
            $this->guard(SecurityScope::partner($this->partner($partnerId))),
        );

        self::assertSame($contractor, $lookup->requireVisible(PartnerId::generate()));
    }

    public function testRequireVisibleHidesAContractorOfAnotherPartner(): void
    {
        $contractor = $this->contractor($this->partner(PartnerId::generate()));

        $lookup = new ScopedContractorLookup(
            $this->contractorsReturning($contractor),
            $this->guard(SecurityScope::partner($this->partner(PartnerId::generate()))),
        );

        $this->expectException(NotFoundHttpException::class);

        $lookup->requireVisible(PartnerId::generate());
    }

    public function testRequireVisibleThrowsWhenTheContractorDoesNotExist(): void
    {
        $repository = $this->createStub(ContractorRepository::class);
        $repository->method('find')->willReturn(null);

        $lookup = new ScopedContractorLookup($repository, $this->guard(SecurityScope::master()));

        $this->expectException(NotFoundHttpException::class);

        $lookup->requireVisible(PartnerId::generate());
    }

    private function contractorsReturning(Contractor $contractor): ContractorRepository
    {
        $repository = $this->createStub(ContractorRepository::class);
        $repository->method('find')->willReturn($contractor);

        return $repository;
    }

    private function guard(SecurityScope $scope): ScopeGuard
    {
        $provider = $this->createStub(SecurityScopeProvider::class);
        $provider->method('scope')->willReturn($scope);

        return new ScopeGuard($provider);
    }

    private function contractor(Partner $partner): Contractor
    {
        $contractor = $this->createStub(Contractor::class);
        $contractor->method('getPartner')->willReturn($partner);

        return $contractor;
    }

    private function partner(PartnerId $id): Partner
    {
        $partner = $this->createStub(Partner::class);
        $partner->method('getId')->willReturn($id);

        return $partner;
    }
}
