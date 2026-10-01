<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\EndToEnd;

use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TokenFlowTest extends WebTestCase
{
    private const string SECURITY_KEY = 'sk_EEEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE';
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
            'HTTP_X_SECURITY_KEY' => self::SECURITY_KEY,
        ], json_encode([
            'username' => 'usere2e',
            'password' => 'wrong-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateRejectsMissingSecurityKey(): void
    {
        $client = static::createClient();
        $this->seedUser();

        $client->request('POST', '/token/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'usere2e',
            'password' => self::PLAIN_PASSWORD,
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }

    public function testVerifyRejectsTokenOfAnotherPartner(): void
    {
        $client = static::createClient();
        $this->seedUser();
        $this->seedOtherPartnerKey();

        $tokens = $this->createToken($client);

        $client->request('POST', '/token/verify', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => 'sk_'.str_repeat('O', 32),
        ], json_encode(['token' => $tokens['accessToken']], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertFalse($payload['valid']);
    }

    private function seedOtherPartnerKey(): void
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $partner = $factory->createPartner('e2e-other');
        $project = $factory->createProject($partner, 'e2e-other');
        $factory->createApiKey($project, 'sk_'.str_repeat('O', 32), 'e2e-other');
    }

    private function seedUser(): void
    {
        $factory = static::getContainer()->get(TestDataFactory::class);
        $partner = $factory->createPartner('e2e');
        $project = $factory->createProject($partner, 'e2e');
        $factory->createApiKey($project, self::SECURITY_KEY, 'e2e');
        $contractor = $factory->createContractor($partner, 'e2e');
        $factory->createUser($contractor, self::PLAIN_PASSWORD, 'e2e');
    }

    /**
     * @return array<string, mixed>
     */
    private function createToken(KernelBrowser $client): array
    {
        $client->request('POST', '/token/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => self::SECURITY_KEY,
        ], json_encode([
            'username' => 'usere2e',
            'password' => self::PLAIN_PASSWORD,
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        return json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    private function verifyToken(KernelBrowser $client, string $accessToken): void
    {
        $client->request('POST', '/token/verify', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SECURITY_KEY' => self::SECURITY_KEY,
        ], json_encode(['token' => $accessToken], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertTrue($payload['valid']);
    }

    /**
     * @return array<string, mixed>
     */
    private function refreshToken(KernelBrowser $client, string $refreshToken): array
    {
        $client->request('POST', '/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        return json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    private function revokeToken(KernelBrowser $client, string $refreshToken): void
    {
        $client->request('POST', '/token/revoke', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
    }

    private function assertRefreshFails(KernelBrowser $client, string $refreshToken): void
    {
        $client->request('POST', '/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['refreshToken' => $refreshToken], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(401);
    }
}
