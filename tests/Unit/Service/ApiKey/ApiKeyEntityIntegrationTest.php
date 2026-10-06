<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\ApiKey;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Service\ApiKey\ApiKeyIssuer;
use Phprise\KoenmaID\Service\ApiKey\IssuedApiKey;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Phprise\KoenmaID\Repository\ProjectRepository;

final class ApiKeyEntityIntegrationTest extends TestCase
{
    public function testIssueCreatesApiKeyWithCorrectSecurityKeyExposure(): void
    {
        $project = $this->createStub(Project::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $issuer = new ApiKeyIssuer($entityManager);
        
        // Mock persist and flush
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $issued = $issuer->issue($project, 'Test Key', 30);

        $this->assertInstanceOf(IssuedApiKey::class, $issued);
        $this->assertInstanceOf(ApiKey::class, $issued->apiKey);
        $this->assertMatchesRegularExpression('/^sk_[A-Za-z0-9]{32}$/', $issued->plainKey);
        $this->assertTrue(str_starts_with($issued->plainKey, 'sk_'));
    }
    
    public function testApiKeyEntityHasSecurityKeyProperty(): void
    {
        $project = $this->createStub(Project::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $issuer = new ApiKeyIssuer($entityManager);
        
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');
        
        $issued = $issuer->issue($project, 'Test Key', 30);
        $apiKey = $issued->apiKey;
        
        // Test that security key property exists and is set correctly
        $this->assertNull($apiKey->getSecurityKey());
        
        $apiKey->setSecurityKey('sk_test_key');
        $this->assertSame('sk_test_key', $apiKey->getSecurityKey());
    }
}