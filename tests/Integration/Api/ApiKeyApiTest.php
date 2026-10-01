<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Api;

use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiKeyApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCreatesApiKeyUnderProject(): void
    {
        $project = $this->seedProject('key-create');

        $this->client->request('POST', '/projects/'.$project->id()->toString().'/api-keys', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Key Alpha'], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        $payload = $this->decode();
        self::assertSame('Key Alpha', $payload['name']);
        self::assertSame($project->id()->toString(), $payload['projectId']);
        self::assertArrayHasKey('keyPrefix', $payload);
        self::assertArrayHasKey('keySuffix', $payload);
        self::assertArrayHasKey('securityKey', $payload);
        self::assertMatchesRegularExpression('/^sk_[A-Za-z0-9]{32}$/', $payload['securityKey']);
    }

    public function testExposesPlainKeyOnlyOnCreation(): void
    {
        $project = $this->seedProject('key-once');

        $this->client->request('POST', '/projects/'.$project->id()->toString().'/api-keys', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Key Once'], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        $created = $this->decode();
        self::assertArrayHasKey('securityKey', $created);

        $this->client->request('GET', '/api-keys/'.$created['id']);

        self::assertResponseIsSuccessful();
        $fetched = $this->decode();
        self::assertArrayNotHasKey('securityKey', $fetched);
    }

    public function testRejectsInvalidApiKeyPayload(): void
    {
        $project = $this->seedProject('key-invalid');

        $this->client->request('POST', '/projects/'.$project->id()->toString().'/api-keys', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => ''], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
    }

    public function testReturnsNotFoundForUnknownProject(): void
    {
        $this->client->request('POST', '/projects/prj_01M2BW4T17D5EG03XCJ8XG0ARR/api-keys', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Orphan Key'], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(404);
    }

    public function testListsApiKeysOfProject(): void
    {
        $project = $this->seedProject('key-list');
        $this->factory()->createApiKey($project, 'plain-key-a', 'list-a');
        $this->factory()->createApiKey($project, 'plain-key-b', 'list-b');

        $this->client->request('GET', '/projects/'.$project->id()->toString().'/api-keys');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(2, $payload);
    }

    public function testListsOnlyApiKeysOfGivenProject(): void
    {
        $partner = $this->factory()->createPartner('key-scope');
        $project = $this->factory()->createProject($partner, 'scope-a');
        $other = $this->factory()->createProject($partner, 'scope-b');
        $this->factory()->createApiKey($project, 'plain-key-scope-a', 'scope-a');
        $this->factory()->createApiKey($other, 'plain-key-scope-b', 'scope-b');

        $this->client->request('GET', '/projects/'.$project->id()->toString().'/api-keys');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($project->id()->toString(), $payload[0]['projectId']);
    }

    public function testListsOnlyNonExpiredApiKeys(): void
    {
        $project = $this->seedProject('key-expired');
        $this->factory()->createApiKey($project, 'plain-key-active', 'active');
        $this->factory()->createApiKey($project, 'plain-key-expired', 'expired', new \DateTimeImmutable('-1 day'));

        $this->client->request('GET', '/projects/'.$project->id()->toString().'/api-keys');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
    }

    public function testGetsApiKeyById(): void
    {
        $project = $this->seedProject('key-get');
        $apiKey = $this->factory()->createApiKey($project, 'plain-key-get', 'get');

        $this->client->request('GET', '/api-keys/'.$apiKey->id()->toString());

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame($apiKey->id()->toString(), $payload['id']);
    }

    public function testPatchesApiKey(): void
    {
        $project = $this->seedProject('key-patch');
        $apiKey = $this->factory()->createApiKey($project, 'plain-key-patch', 'patch');

        $this->client->request('PATCH', '/api-keys/'.$apiKey->id()->toString(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode(['name' => 'Renamed Key'], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame('Renamed Key', $payload['name']);
    }

    public function testDeletesApiKeyWithValidSecurityKey(): void
    {
        $project = $this->seedProjectWithKey('key-delete');
        $apiKey = $this->factory()->createApiKey($project, 'plain-key-delete', 'delete');

        $this->client->request('DELETE', '/api-keys/'.$apiKey->id()->toString(), [], [], [
            'HTTP_X_SECURITY_KEY' => $this->securityKeyFor('key-delete'),
        ]);

        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api-keys/'.$apiKey->id()->toString());
        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteRejectsMissingSecurityKey(): void
    {
        $project = $this->seedProjectWithKey('key-delete-no-key');
        $apiKey = $this->factory()->createApiKey($project, 'plain-key-delete-no-key', 'delete-no-key');

        $this->client->request('DELETE', '/api-keys/'.$apiKey->id()->toString());

        self::assertResponseStatusCodeSame(401);
    }

    public function testDeleteRejectsSecurityKeyOfAnotherPartner(): void
    {
        $project = $this->seedProjectWithKey('key-delete-own');
        $this->seedProjectWithKey('key-delete-other');
        $apiKey = $this->factory()->createApiKey($project, 'plain-key-delete-own', 'delete-own');

        $this->client->request('DELETE', '/api-keys/'.$apiKey->id()->toString(), [], [], [
            'HTTP_X_SECURITY_KEY' => $this->securityKeyFor('key-delete-other'),
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testReturnsNotFoundForUnknownApiKey(): void
    {
        $this->client->request('GET', '/api-keys/aky_01M2BW4T17D5EG03XCJ8XG0ARR');

        self::assertResponseStatusCodeSame(404);
    }

    private function seedProject(string $suffix): \Phprise\KoenmaID\Entity\Project
    {
        $partner = $this->factory()->createPartner($suffix);

        return $this->factory()->createProject($partner, $suffix);
    }

    private function seedProjectWithKey(string $suffix): \Phprise\KoenmaID\Entity\Project
    {
        $project = $this->seedProject($suffix);
        $this->factory()->createApiKey($project, $this->securityKeyFor($suffix), $suffix);

        return $project;
    }

    private function securityKeyFor(string $suffix): string
    {
        return 'sk_'.str_pad(substr(md5($suffix), 0, 32), 32, '0');
    }

    private function factory(): TestDataFactory
    {
        return static::getContainer()->get(TestDataFactory::class);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decode(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeCollection(): array
    {
        return $this->decode()['member'];
    }
}
