<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Api;

use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserApiTest extends WebTestCase
{
    private const string PLAIN_API_KEY = 'user-api-plain-key';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCreatesUserWithValidApiKey(): void
    {
        $this->seedApiKey('user-create');

        $this->client->request('POST', '/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'apiKey' => self::PLAIN_API_KEY,
            'contractorName' => 'Contractor Alpha',
            'contractorDocument' => 'doc-user-create',
            'username' => 'newuser',
            'emailAddress' => 'newuser@example.com',
            'password' => 'plain-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        $payload = $this->decode();
        self::assertSame('newuser', $payload['username']);
        self::assertSame('newuser@example.com', $payload['emailAddress']);
        self::assertArrayHasKey('id', $payload);
    }

    public function testRejectsInvalidUserPayload(): void
    {
        $this->seedApiKey('user-invalid');

        $this->client->request('POST', '/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'apiKey' => self::PLAIN_API_KEY,
            'contractorName' => 'Contractor Alpha',
            'contractorDocument' => 'doc-user-invalid',
            'username' => 'ab',
            'emailAddress' => 'not-an-email',
            'password' => 'short',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
    }

    public function testRejectsUnknownApiKey(): void
    {
        $this->client->request('POST', '/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'apiKey' => 'unknown-plain-key',
            'contractorName' => 'Contractor Alpha',
            'contractorDocument' => 'doc-user-unknown',
            'username' => 'newuser',
            'emailAddress' => 'newuser@example.com',
            'password' => 'plain-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testListsUsersOfContractor(): void
    {
        $partner = $this->factory()->createPartner('user-list');
        $contractor = $this->factory()->createContractor($partner, 'list');
        $this->factory()->createUser($contractor, 'plain-password', 'list-a');
        $this->factory()->createUser($contractor, 'plain-password', 'list-b');

        $this->client->request('GET', '/contractors/'.$contractor->id()->toString().'/users');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(2, $payload);
    }

    public function testListsOnlyUsersOfGivenContractor(): void
    {
        $partner = $this->factory()->createPartner('user-scope');
        $contractor = $this->factory()->createContractor($partner, 'scope-a');
        $other = $this->factory()->createContractor($partner, 'scope-b');
        $this->factory()->createUser($contractor, 'plain-password', 'scope-a');
        $this->factory()->createUser($other, 'plain-password', 'scope-b');

        $this->client->request('GET', '/contractors/'.$contractor->id()->toString().'/users');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($contractor->id()->toString(), $payload[0]['contractorId']);
    }

    public function testGetsUserById(): void
    {
        $partner = $this->factory()->createPartner('user-get');
        $contractor = $this->factory()->createContractor($partner, 'get');
        $user = $this->factory()->createUser($contractor, 'plain-password', 'get');

        $this->client->request('GET', '/users/'.$user->id()->toString());

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame($user->id()->toString(), $payload['id']);
    }

    public function testPatchesUser(): void
    {
        $partner = $this->factory()->createPartner('user-patch');
        $contractor = $this->factory()->createContractor($partner, 'patch');
        $user = $this->factory()->createUser($contractor, 'plain-password', 'patch');

        $this->client->request('PATCH', '/users/'.$user->id()->toString(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode(['username' => 'renameduser'], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame('renameduser', $payload['username']);
    }

    public function testReturnsNotFoundForUnknownUser(): void
    {
        $this->client->request('GET', '/users/usr_01M2BW4T17D5EG03XCJ8XG0ARR');

        self::assertResponseStatusCodeSame(404);
    }

    private function seedApiKey(string $suffix): void
    {
        $partner = $this->factory()->createPartner($suffix);
        $project = $this->factory()->createProject($partner, $suffix);
        $this->factory()->createApiKey($project, self::PLAIN_API_KEY, $suffix);
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
