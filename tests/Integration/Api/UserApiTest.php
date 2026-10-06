<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Api;

use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserApiTest extends WebTestCase
{
    private const string SECURITY_KEY = 'sk_UUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCreatesUserUnderContractor(): void
    {
        $contractor = $this->seedContractorWithKey('user-create');

        $this->client->request('POST', '/contractors/'.$contractor->getId()->toString().'/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => $this->securityKeyFor('user-create'),
        ], json_encode([
            'username' => 'newuser',
            'emailAddress' => 'newuser@example.com',
            'password' => 'plain-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        $payload = $this->decode();
        self::assertSame('newuser', $payload['username']);
        self::assertSame('newuser@example.com', $payload['emailAddress']);
        self::assertSame($contractor->getId()->toString(), $payload['contractorId']);
        self::assertArrayHasKey('id', $payload);
    }

    public function testRejectsInvalidUserPayload(): void
    {
        $contractor = $this->seedContractorWithKey('user-invalid');

        $this->client->request('POST', '/contractors/'.$contractor->getId()->toString().'/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => $this->securityKeyFor('user-invalid'),
        ], json_encode([
            'username' => 'ab',
            'emailAddress' => 'not-an-email',
            'password' => 'short',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
    }

    public function testRejectsMissingSecurityKey(): void
    {
        $contractor = $this->seedContractorWithKey('user-no-key');

        $this->client->request('POST', '/contractors/'.$contractor->getId()->toString().'/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'newuser',
            'emailAddress' => 'newuser@example.com',
            'password' => 'plain-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testRejectsSecurityKeyOfAnotherPartner(): void
    {
        $contractor = $this->seedContractorWithKey('user-own');
        $this->seedContractorWithKey('user-other');

        $this->client->request('POST', '/contractors/'.$contractor->getId()->toString().'/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => 'sk_OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO',
        ], json_encode([
            'username' => 'newuser',
            'emailAddress' => 'newuser@example.com',
            'password' => 'plain-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testReturnsNotFoundForUnknownContractor(): void
    {
        $this->seedContractorWithKey('user-unknown');

        $this->client->request('POST', '/contractors/cnt_01M2BW4T17D5EG03XCJ8XG0ARR/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => $this->securityKeyFor('user-unknown'),
        ], json_encode([
            'username' => 'newuser',
            'emailAddress' => 'newuser@example.com',
            'password' => 'plain-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(404);
    }

    public function testAllowsSameUsernameInDifferentContractors(): void
    {
        $first = $this->seedContractorWithKey('user-dup-a');
        $second = $this->seedContractorWithKey('user-dup-b');

        $this->createUser($first, 'shareduser', 'user-dup-a');
        $this->createUser($second, 'shareduser', 'user-dup-b');

        self::assertResponseStatusCodeSame(201);
    }

    public function testRejectsDuplicateUsernameInSameContractor(): void
    {
        $contractor = $this->seedContractorWithKey('user-dup-same');

        $this->createUser($contractor, 'duplicated', 'user-dup-same');
        $this->createUser($contractor, 'duplicated', 'user-dup-same');

        self::assertResponseStatusCodeSame(409);
    }

    public function testListsUsersOfContractor(): void
    {
        $partner = $this->factory()->createPartner('user-list');
        $contractor = $this->factory()->createContractor($partner, 'list');
        $this->factory()->createUser($contractor, 'plain-password', 'list-a');
        $this->factory()->createUser($contractor, 'plain-password', 'list-b');

        $this->client->request('GET', '/contractors/'.$contractor->getId()->toString().'/users');

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

        $this->client->request('GET', '/contractors/'.$contractor->getId()->toString().'/users');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($contractor->getId()->toString(), $payload[0]['contractorId']);
    }

    public function testGetsUserById(): void
    {
        $partner = $this->factory()->createPartner('user-get');
        $contractor = $this->factory()->createContractor($partner, 'get');
        $user = $this->factory()->createUser($contractor, 'plain-password', 'get');

        $this->client->request('GET', '/users/'.$user->getId()->toString());

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame($user->getId()->toString(), $payload['id']);
    }

    public function testPatchesUser(): void
    {
        $partner = $this->factory()->createPartner('user-patch');
        $contractor = $this->factory()->createContractor($partner, 'patch');
        $user = $this->factory()->createUser($contractor, 'plain-password', 'patch');

        $this->client->request('PATCH', '/users/'.$user->getId()->toString(), [], [], [
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

    private function seedContractorWithKey(string $suffix): \Phprise\KoenmaID\Entity\Contractor
    {
        $partner = $this->factory()->createPartner($suffix);
        $project = $this->factory()->createProject($partner, $suffix);
        $this->factory()->createApiKey($project, $this->securityKeyFor($suffix), $suffix);

        return $this->factory()->createContractor($partner, $suffix);
    }

    private function securityKeyFor(string $suffix): string
    {
        return 'sk_'.str_pad(substr(md5($suffix), 0, 32), 32, '0');
    }

    private function createUser(\Phprise\KoenmaID\Entity\Contractor $contractor, string $username, string $suffix): void
    {
        $this->client->request('POST', '/contractors/'.$contractor->getId()->toString().'/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => $this->securityKeyFor($suffix),
        ], json_encode([
            'username' => $username,
            'emailAddress' => $username.'@example.com',
            'password' => 'plain-password',
        ], \JSON_THROW_ON_ERROR));
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
