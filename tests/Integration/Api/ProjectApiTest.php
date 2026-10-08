<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Api;

use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProjectApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCreatesProjectUnderPartner(): void
    {
        $partner = $this->factory()->createPartner('proj-create');

        $this->client->request('POST', '/partners/'.$partner->getId()->toString().'/projects', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Project Alpha',
            'description' => 'First project',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        $payload = $this->decode();
        self::assertSame('Project Alpha', $payload['name']);
        self::assertSame('First project', $payload['description']);
        self::assertSame($partner->getId()->toString(), $payload['partnerId']);
        self::assertArrayHasKey('id', $payload);
    }

    public function testRejectsInvalidProjectPayload(): void
    {
        $partner = $this->factory()->createPartner('proj-invalid');

        $this->client->request('POST', '/partners/'.$partner->getId()->toString().'/projects', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => ''], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
    }

    public function testReturnsNotFoundForUnknownPartner(): void
    {
        $this->client->request('POST', '/partners/prt_01M2BW4T17D5EG03XCJ8XG0ARR/projects', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Orphan'], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(404);
    }

    public function testListsProjectsOfPartner(): void
    {
        $partner = $this->factory()->createPartner('proj-list');
        $this->factory()->createProject($partner, 'list-a');
        $this->factory()->createProject($partner, 'list-b');

        $this->client->request('GET', '/partners/'.$partner->getId()->toString().'/projects');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(2, $payload);
    }

    public function testListsOnlyProjectsOfGivenPartner(): void
    {
        $partner = $this->factory()->createPartner('proj-scope-a');
        $other = $this->factory()->createPartner('proj-scope-b');
        $this->factory()->createProject($partner, 'scope-a');
        $this->factory()->createProject($other, 'scope-b');

        $this->client->request('GET', '/partners/'.$partner->getId()->toString().'/projects');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($partner->getId()->toString(), $payload[0]['partnerId']);
    }

    public function testGetsProjectById(): void
    {
        $partner = $this->factory()->createPartner('proj-get');
        $project = $this->factory()->createProject($partner, 'get');

        $this->client->request('GET', '/projects/'.$project->getId()->toString());

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame($project->getId()->toString(), $payload['id']);
    }

    public function testPatchesProject(): void
    {
        $partner = $this->factory()->createPartner('proj-patch');
        $project = $this->factory()->createProject($partner, 'patch');

        $this->client->request('PATCH', '/projects/'.$project->getId()->toString(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode(['name' => 'Renamed Project'], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame('Renamed Project', $payload['name']);
    }

    public function testReturnsNotFoundForUnknownProject(): void
    {
        $this->client->request('GET', '/projects/prj_01M2BW4T17D5EG03XCJ8XG0ARR');

        self::assertResponseStatusCodeSame(404);
    }

    public function testListsApiKeysOfProject(): void
    {
        $partner = $this->factory()->createPartner('proj-key-list');
        $project = $this->factory()->createProject($partner, 'key-list');
        $this->factory()->createApiKey($project, 'plain-key-a', 'list-a');
        $this->factory()->createApiKey($project, 'plain-key-b', 'list-b');

        $this->client->request('GET', '/projects/'.$project->getId()->toString().'/api-keys');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(2, $payload);
    }

    public function testListsOnlyApiKeysOfGivenProject(): void
    {
        $partner = $this->factory()->createPartner('proj-key-scope');
        $project = $this->factory()->createProject($partner, 'scope-a');
        $other = $this->factory()->createProject($partner, 'scope-b');
        $this->factory()->createApiKey($project, 'plain-key-scope-a', 'scope-a');
        $this->factory()->createApiKey($other, 'plain-key-scope-b', 'scope-b');

        $this->client->request('GET', '/projects/'.$project->getId()->toString().'/api-keys');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($project->getId()->toString(), $payload[0]['projectId']);
    }

    public function testListsOnlyNonExpiredApiKeys(): void
    {
        $partner = $this->factory()->createPartner('proj-key-expired');
        $project = $this->factory()->createProject($partner, 'key-expired');
        $this->factory()->createApiKey($project, 'plain-key-active', 'active');
        $this->factory()->createApiKey($project, 'plain-key-expired', 'expired', new \DateTimeImmutable('-1 day'));

        $this->client->request('GET', '/projects/'.$project->getId()->toString().'/api-keys');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
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
