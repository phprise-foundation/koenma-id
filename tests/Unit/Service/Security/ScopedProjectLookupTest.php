<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Phprise\KoenmaID\Service\Security\ScopedProjectLookup;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Phprise\KoenmaID\Service\Security\SecurityScopeProvider;
use Phprise\KoenmaID\ValueObject\PartnerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ScopedProjectLookupTest extends TestCase
{
    public function testRequireVisibleReturnsAProjectOfTheUnrestrictedScope(): void
    {
        $project = $this->project($this->partner(PartnerId::generate()));

        $lookup = new ScopedProjectLookup($this->projectsReturning($project), $this->guard(SecurityScope::master()));

        self::assertSame($project, $lookup->requireVisible(PartnerId::generate()));
    }

    public function testRequireVisibleReturnsAProjectOfTheScopedPartner(): void
    {
        $partnerId = PartnerId::generate();
        $project = $this->project($this->partner($partnerId));

        $lookup = new ScopedProjectLookup(
            $this->projectsReturning($project),
            $this->guard(SecurityScope::partner($this->partner($partnerId))),
        );

        self::assertSame($project, $lookup->requireVisible(PartnerId::generate()));
    }

    public function testRequireVisibleHidesAProjectOfAnotherPartner(): void
    {
        $project = $this->project($this->partner(PartnerId::generate()));

        $lookup = new ScopedProjectLookup(
            $this->projectsReturning($project),
            $this->guard(SecurityScope::partner($this->partner(PartnerId::generate()))),
        );

        $this->expectException(NotFoundHttpException::class);

        $lookup->requireVisible(PartnerId::generate());
    }

    public function testRequireVisibleThrowsWhenTheProjectDoesNotExist(): void
    {
        $repository = $this->createStub(ProjectRepository::class);
        $repository->method('find')->willReturn(null);

        $lookup = new ScopedProjectLookup($repository, $this->guard(SecurityScope::master()));

        $this->expectException(NotFoundHttpException::class);

        $lookup->requireVisible(PartnerId::generate());
    }

    private function projectsReturning(Project $project): ProjectRepository
    {
        $repository = $this->createStub(ProjectRepository::class);
        $repository->method('find')->willReturn($project);

        return $repository;
    }

    private function guard(SecurityScope $scope): ScopeGuard
    {
        $provider = $this->createStub(SecurityScopeProvider::class);
        $provider->method('scope')->willReturn($scope);

        return new ScopeGuard($provider);
    }

    private function project(Partner $partner): Project
    {
        $project = $this->createStub(Project::class);
        $project->method('getPartner')->willReturn($partner);

        return $project;
    }

    private function partner(PartnerId $id): Partner
    {
        $partner = $this->createStub(Partner::class);
        $partner->method('getId')->willReturn($id);

        return $partner;
    }
}
