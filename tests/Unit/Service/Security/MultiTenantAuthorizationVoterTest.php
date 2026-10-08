<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Service\Security\MultiTenantAuthorizationVoter;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Phprise\KoenmaID\Service\Security\SecurityScopeProvider;
use Phprise\KoenmaID\ValueObject\PartnerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class MultiTenantAuthorizationVoterTest extends TestCase
{
    public function testAbstainsOnAnUnsupportedAttribute(): void
    {
        $voter = $this->voter(SecurityScope::master());

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->token(), null, ['UNKNOWN']),
        );
    }

    public function testAbstainsOnAnUnsupportedSubject(): void
    {
        $voter = $this->voter(SecurityScope::master());

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->token(), new \stdClass(), [MultiTenantAuthorizationVoter::VIEW]),
        );
    }

    public function testGrantsEverySupportedAttributeForMasterScope(): void
    {
        $voter = $this->voter(SecurityScope::master());

        foreach ([
            MultiTenantAuthorizationVoter::VIEW,
            MultiTenantAuthorizationVoter::EDIT,
            MultiTenantAuthorizationVoter::DELETE,
        ] as $attribute) {
            self::assertSame(
                VoterInterface::ACCESS_GRANTED,
                $voter->vote($this->token(), null, [$attribute]),
                \sprintf('Master scope should be granted for "%s".', $attribute),
            );
        }
    }

    public function testDeniesAccessForAnonymousScope(): void
    {
        $voter = $this->voter(SecurityScope::anonymous());

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token(), null, [MultiTenantAuthorizationVoter::VIEW]),
        );
    }

    public function testGrantsPartnerScopeAccessToItsOwnPartner(): void
    {
        $partnerId = PartnerId::generate();
        $voter = $this->voter(SecurityScope::partner($this->partner($partnerId)));

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token(), $this->partner($partnerId), [MultiTenantAuthorizationVoter::VIEW]),
        );
    }

    public function testDeniesPartnerScopeAccessToAnotherPartner(): void
    {
        $voter = $this->voter(SecurityScope::partner($this->partner(PartnerId::generate())));

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token(), $this->partner(PartnerId::generate()), [MultiTenantAuthorizationVoter::VIEW]),
        );
    }

    public function testGrantsPartnerScopeAccessToAProjectOfItsOwnPartner(): void
    {
        $partnerId = PartnerId::generate();
        $voter = $this->voter(SecurityScope::partner($this->partner($partnerId)));

        $project = $this->project($this->partner($partnerId));

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote($this->token(), $project, [MultiTenantAuthorizationVoter::DELETE]),
        );
    }

    public function testDeniesPartnerScopeAccessToAProjectOfAnotherPartner(): void
    {
        $voter = $this->voter(SecurityScope::partner($this->partner(PartnerId::generate())));

        $project = $this->project($this->partner(PartnerId::generate()));

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote($this->token(), $project, [MultiTenantAuthorizationVoter::EDIT]),
        );
    }

    private function voter(SecurityScope $scope): MultiTenantAuthorizationVoter
    {
        $provider = $this->createStub(SecurityScopeProvider::class);
        $provider->method('scope')->willReturn($scope);

        return new MultiTenantAuthorizationVoter($provider);
    }

    private function token(): TokenInterface
    {
        return $this->createStub(TokenInterface::class);
    }

    private function partner(PartnerId $id): Partner
    {
        $partner = $this->createStub(Partner::class);
        $partner->method('getId')->willReturn($id);

        return $partner;
    }

    private function project(Partner $partner): Project
    {
        $project = $this->createStub(Project::class);
        $project->method('getPartner')->willReturn($partner);

        return $project;
    }
}
