<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Api;

use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PartnerApiTest extends WebTestCase
{
    private const string MASTER_KEY = 'sk_MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->loginUser($this->createAuthenticatedUser());
    }

    public function testCreatesPartner(): void
    {
        $this->client->request('POST', '/partners', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => self::MASTER_KEY,
        ], json_encode([
            'name' => 'Acme',
            'emailAddress' => 'acme@example.com',
            'document' => 'doc-acme',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        fwrite(STDERR, "\nDEBUG: ".$this->client->getResponse()->getContent()."\n");
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Acme', $payload['name']);
        self::assertSame('acme@example.com', $payload['emailAddress']);
        self::assertArrayHasKey('id', $payload);
    }

    public function testRejectsInvalidPayload(): void
    {
        $this->client->request('POST', '/partners', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => self::MASTER_KEY,
        ], json_encode([
            'name' => '',
            'emailAddress' => 'not-an-email',
            'document' => '',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
    }

    public function testListsPartners(): void
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $factory->createPartner('list');

        $this->client->request('GET', '/partners');

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertNotEmpty($payload);
    }

    public function testPatchesPartner(): void
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $partner = $factory->createPartner('patch');

        $this->client->request('PATCH', '/partners/'.$partner->getId()->toString(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_X_SECURITY_KEY' => self::MASTER_KEY,
        ], json_encode(['name' => 'Renamed'], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Renamed', $payload['name']);
    }

    public function testCreateRejectsMissingMasterKey(): void
    {
        $this->client->request('POST', '/partners', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Acme',
            'emailAddress' => 'acme@example.com',
            'document' => 'doc-acme',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testPatchRejectsMissingMasterKey(): void
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $partner = $factory->createPartner('patch-no-key');

        $this->client->request('PATCH', '/partners/'.$partner->getId()->toString(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode(['name' => 'Renamed'], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testReturnsNotFoundForUnknownIdentifier(): void
    {
        $this->client->request('GET', '/partners/prt_01M2BW4T17D5EG03XCJ8XG0ARR');

        self::assertResponseStatusCodeSame(404);
    }

    public function testReturnsNotFoundForMalformedIdentifier(): void
    {
        $this->client->request('GET', '/partners/not-a-valid-id');

        self::assertResponseStatusCodeSame(404);
    }

    private function createAuthenticatedUser(): User
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $partner = $factory->createPartner('auth');
        $contractor = $factory->createContractor($partner, 'auth');

        return $factory->createUser($contractor, 'plain-password', 'auth');
    }

    public function testCreateRejectsPartnerKeyWith403(): void
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $partner = $factory->createPartner('partner-key-403');
        $project = $factory->createProject($partner, 'partner-key-403');
        $plainKey = 'sk_12345678901234567890123456789012';
        $factory->createApiKey($project, $plainKey, 'partner-key-403');

        $this->client->request('POST', '/partners', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => $plainKey,
        ], json_encode([
            'name' => 'Acme Attempt',
            'emailAddress' => 'attempt@example.com',
            'document' => 'doc-attempt',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(403);
    }

    public function testPatchRejectsPartnerKeyWith403(): void
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $partner = $factory->createPartner('partner-patch-403');
        $project = $factory->createProject($partner, 'partner-patch-403');
        $plainKey = 'sk_22345678901234567890123456789012';
        $factory->createApiKey($project, $plainKey, 'partner-patch-403');

        $this->client->request('PATCH', '/partners/'.$partner->getId()->toString(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_X_SECURITY_KEY' => $plainKey,
        ], json_encode(['name' => 'Renamed Attempt'], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(403);
    }
}
