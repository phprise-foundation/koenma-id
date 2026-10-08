<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Phprise\KoenmaID\Service\Security\ScopedProjectLookup;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Phprise\KoenmaID\Service\Security\SecurityScopeProvider;
use Phprise\KoenmaID\State\ApiKey\ApiKeyCollectionProvider;
use Phprise\KoenmaID\ValueObject\PartnerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ApiKeyCollectionProviderTest extends TestCase
{
    public function testProvideReturnsApiKeysOfProjectInUnrestrictedScope(): void
    {
        $project = $this->project($this->partner(PartnerId::generate()));
        $apiKey = $this->apiKey($project);

        $provider = new ApiKeyCollectionProvider(
            $this->apiKeyRepository([$apiKey]),
            $this->lookup($project, SecurityScope::master()),
        );

        $result = $provider->provide($this->operation(), ['projectId' => 'prj_123']);

        self::assertSame([$apiKey], $result);
    }

    public function testProvideReturnsApiKeysOfProjectInScopedPartner(): void
    {
        $partnerId = PartnerId::generate();
        $project = $this->project($this->partner($partnerId));
        $apiKey = $this->apiKey($project);

        $provider = new ApiKeyCollectionProvider(
            $this->apiKeyRepository([$apiKey]),
            $this->lookup($project, SecurityScope::partner($this->partner($partnerId))),
        );

        $result = $provider->provide($this->operation(), ['projectId' => 'prj_123']);

        self::assertSame([$apiKey], $result);
    }

    public function testProvideHidesApiKeysOfProjectOfAnotherPartner(): void
    {
        $project = $this->project($this->partner(PartnerId::generate()));

        $provider = new ApiKeyCollectionProvider(
            $this->apiKeyRepository([]),
            $this->lookup($project, SecurityScope::partner($this->partner(PartnerId::generate()))),
        );

        $this->expectException(NotFoundHttpException::class);

        $provider->provide($this->operation(), ['projectId' => 'prj_123']);
    }

    private function operation(): Operation
    {
        return $this->createStub(Operation::class);
    }

    private function lookup(Project $project, SecurityScope $scope): ScopedProjectLookup
    {
        $projects = $this->createStub(ProjectRepository::class);
        $projects->method('find')->willReturn($project);

        $scopeProvider = $this->createStub(SecurityScopeProvider::class);
        $scopeProvider->method('scope')->willReturn($scope);

        return new ScopedProjectLookup($projects, new ScopeGuard($scopeProvider));
    }

    private function apiKeyRepository(array $apiKeys): ApiKeyRepository
    {
        $repository = $this->createStub(ApiKeyRepository::class);
        $repository->method('findActiveByProject')->willReturn($apiKeys);

        return $repository;
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

    private function apiKey(Project $project): ApiKey
    {
        $apiKey = $this->createStub(ApiKey::class);
        $apiKey->method('getProject')->willReturn($project);

        return $apiKey;
    }
}
