<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\EndToEnd;

use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TokenFlowTest extends WebTestCase
{
    private const string PLAIN_API_KEY = 'e2e-plain-api-key';
    private const string PLAIN_PASSWORD = 'plain-password';

    public function testFullTokenLifecycle(): void
    {
        $client = static::createClient();
        $this->seedUser();

        $tokens = $this->createToken($client);
        self::assertArrayHasKey('accessToken', $tokens);
        self::assertArrayHasKey('refreshToken', $tokens);

        $this->verifyToken($client, $tokens['accessToken']);

        $refreshed = $this->refreshToken($client, $tokens['refreshToken']);
        self::assertArrayHasKey('accessToken', $refreshed);

        $this->revokeToken($client, $refreshed['refreshToken']);
        $this->assertRefreshFails($client, $refreshed['refreshToken']);
    }

    public function testCreateRejectsWrongPassword(): void
    {
        $client = static::createClient();
        $this->seedUser();

        $client->request('POST', '/token/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'apiKey' => self::PLAIN_API_KEY,
            'username' => 'usere2e',
            'password' => 'wrong-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    private function seedUser(): void
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $partner = $factory->createPartner('e2e');
        $project = $factory->createProject($partner, 'e2e');
        $factory->createApiKey($project, self::PLAIN_API_KEY, 'e2e');
        $contractor = $factory->createContractor($partner, 'e2e');
        $factory->createUser($contractor, self::PLAIN_PASSWORD, 'e2e');
    }

    /**
     * @return array<string, mixed>
     */
    private function createToken(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): array
    {
        $client->request('POST', '/token/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'apiKey' => self::PLAIN_API_KEY,
            'username' => 'usere2e',
            'password' => self::PLAIN_PASSWORD,
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        return json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    private function verifyToken(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $accessToken): void
    {
        $client->request('POST', '/token/verify', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['token' => $accessToken], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertTrue($payload['valid']);
    }

    /**
     * @return array<string, mixed>
     */
    private function refreshToken(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $refreshToken): array
    {
        $client->request('POST', '/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        return json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    private function revokeToken(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $refreshToken): void
    {
        $client->request('POST', '/token/revoke', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
    }

    private function assertRefreshFails(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $refreshToken): void
    {
        $client->request('POST', '/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }
}
