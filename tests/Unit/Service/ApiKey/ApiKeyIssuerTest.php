<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\ApiKey;

use Doctrine\ORM\EntityManagerInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Service\ApiKey\ApiKeyIssuer;
use Phprise\KoenmaID\Service\ApiKey\IssuedApiKey;
use PHPUnit\Framework\TestCase;

final class ApiKeyIssuerTest extends TestCase
{
    public function testIssueGeneratesPlainKeyAndPersistsDerivedFields(): void
    {
        $project = $this->createStub(Project::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $issuer = new ApiKeyIssuer($entityManager);
        $issued = $issuer->issue($project, 'Test Key', 30);

        self::assertInstanceOf(IssuedApiKey::class, $issued);
        self::assertInstanceOf(ApiKey::class, $issued->apiKey);
        self::assertMatchesRegularExpression('/^sk_[A-Za-z0-9]{32}$/', $issued->plainKey);

        $apiKey = $issued->apiKey;
        self::assertSame('Test Key', $apiKey->getName());
        self::assertSame($project, $apiKey->getProject());
        self::assertSame(substr($issued->plainKey, 0, 8), $apiKey->getKeyPrefix());
        self::assertSame(substr($issued->plainKey, -8), $apiKey->getKeySuffix());
        self::assertSame(hash('sha256', $issued->plainKey), $apiKey->getKeyHash());
        self::assertNotSame($issued->plainKey, $apiKey->getKeyHash());
        self::assertStringNotContainsString($issued->plainKey, $apiKey->getKeyHash());
    }

    public function testIssueWithoutExpirationLeavesExpiresAtNull(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $issuer = new ApiKeyIssuer($entityManager);

        $issued = $issuer->issue($this->createStub(Project::class), 'No expiry', null);

        self::assertNull($issued->apiKey->getExpiresAt());
    }

    public function testIssueSetsExpirationInTheFuture(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $issuer = new ApiKeyIssuer($entityManager);

        $issued = $issuer->issue($this->createStub(Project::class), 'Expiring', 30);

        self::assertNotNull($issued->apiKey->getExpiresAt());
        self::assertGreaterThan(new \DateTimeImmutable('+29 days'), $issued->apiKey->getExpiresAt());
    }

    public function testIssueDoesNotStorePlainKeyInSecurityKey(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $issuer = new ApiKeyIssuer($entityManager);

        $issued = $issuer->issue($this->createStub(Project::class), 'Transient', null);

        self::assertNull($issued->apiKey->getSecurityKey());
    }
}
