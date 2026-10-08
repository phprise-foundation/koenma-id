<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Api;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PartnerSegregationMutationTest extends WebTestCase
{
    private const string MASTER_KEY = 'sk_MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    // ---- RFC-002-7-1: Partners -------------------------------------------------

    public function testMasterGetsAnyPartner(): void
    {
        $partner = $this->factory()->createPartner('m-mas-get-par');

        $this->request('GET', '/partners/'.$partner->getId()->toString(), self::MASTER_KEY);

        self::assertResponseIsSuccessful();
        self::assertSame($partner->getId()->toString(), $this->decode()['id']);
    }

    public function testPartnerKeyGetsItsOwnPartner(): void
    {
        $partner = $this->seededPartner('m-own-get-par');

        $this->request('GET', '/partners/'.$partner->getId()->toString(), $this->securityKey('m-own-get-par'));

        self::assertResponseIsSuccessful();
        self::assertSame($partner->getId()->toString(), $this->decode()['id']);
    }

    public function testPartnerKeyHidesAnotherPartner(): void
    {
        $this->seededPartner('m-key-get-par');
        $foreign = $this->factory()->createPartner('m-for-get-par');

        $this->request('GET', '/partners/'.$foreign->getId()->toString(), $this->securityKey('m-key-get-par'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testMasterPatchesAnyPartner(): void
    {
        $partner = $this->factory()->createPartner('m-mas-pat-par');

        $this->request('PATCH', '/partners/'.$partner->getId()->toString(), self::MASTER_KEY, ['name' => 'Master Renamed']);

        self::assertResponseIsSuccessful();
        self::assertSame('Master Renamed', $this->decode()['name']);
    }

    public function testPartnerKeyPatchesItsOwnPartner(): void
    {
        $partner = $this->seededPartner('m-own-pat-par');

        $this->request('PATCH', '/partners/'.$partner->getId()->toString(), $this->securityKey('m-own-pat-par'), ['name' => 'Own Renamed']);

        self::assertResponseIsSuccessful();
        self::assertSame('Own Renamed', $this->decode()['name']);
    }

    public function testPartnerKeyCannotPatchAnotherPartner(): void
    {
        $this->seededPartner('m-key-pat-par');
        $foreign = $this->factory()->createPartner('m-for-pat-par');

        $this->request('PATCH', '/partners/'.$foreign->getId()->toString(), $this->securityKey('m-key-pat-par'), ['name' => 'Hijacked']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testMasterDeletesAnyPartner(): void
    {
        $partner = $this->factory()->createPartner('m-mas-del-par');

        $this->request('DELETE', '/partners/'.$partner->getId()->toString(), self::MASTER_KEY);

        self::assertResponseStatusCodeSame(204);
    }

    public function testPartnerKeyDeletesItsOwnPartner(): void
    {
        $partner = $this->seededPartner('m-own-del-par');

        $this->request('DELETE', '/partners/'.$partner->getId()->toString(), $this->securityKey('m-own-del-par'));

        self::assertResponseStatusCodeSame(204);
    }

    public function testPartnerKeyCannotDeleteAnotherPartner(): void
    {
        $this->seededPartner('m-key-del-par');
        $foreign = $this->factory()->createPartner('m-for-del-par');

        $this->request('DELETE', '/partners/'.$foreign->getId()->toString(), $this->securityKey('m-key-del-par'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeletingPartnerWithoutKeyIsRejected(): void
    {
        $partner = $this->seededPartner('m-nok-del-par');

        $this->request('DELETE', '/partners/'.$partner->getId()->toString());

        self::assertResponseStatusCodeSame(401);
    }

    // ---- RFC-002-7-2: Projects -------------------------------------------------

    public function testProjectMutationCoversEveryScope(): void
    {
        [, $project] = $this->seededPartnerWithProject('m-prj-own');
        $foreign = $this->seededPartnerWithProject('m-prj-for');
        $ownKey = $this->securityKey('m-prj-own');

        $this->request('GET', '/projects/'.$foreign[1]->getId()->toString(), self::MASTER_KEY);
        self::assertResponseIsSuccessful();
        self::assertSame($foreign[1]->getId()->toString(), $this->decode()['id']);

        $this->request('GET', '/projects/'.$project->getId()->toString(), $ownKey);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/projects/'.$foreign[1]->getId()->toString(), $ownKey);
        self::assertResponseStatusCodeSame(404);

        $this->request('PATCH', '/projects/'.$foreign[1]->getId()->toString(), self::MASTER_KEY, ['name' => 'Master Project']);
        self::assertResponseIsSuccessful();

        $this->request('PATCH', '/projects/'.$project->getId()->toString(), $ownKey, ['name' => 'Own Project']);
        self::assertResponseIsSuccessful();
        self::assertSame('Own Project', $this->decode()['name']);

        $this->request('PATCH', '/projects/'.$foreign[1]->getId()->toString(), $ownKey, ['name' => 'Hijacked Project']);
        self::assertResponseStatusCodeSame(404);

        $this->request('DELETE', '/projects/'.$foreign[1]->getId()->toString(), self::MASTER_KEY);
        self::assertResponseStatusCodeSame(204);

        $this->request('DELETE', '/projects/'.$project->getId()->toString(), $ownKey);
        self::assertResponseStatusCodeSame(204);
    }

    public function testPartnerKeyCannotDeleteAnotherPartnerProject(): void
    {
        $this->seededPartnerWithProject('m-prj-del-own');
        $foreign = $this->seededPartnerWithProject('m-prj-del-for');

        $this->request('DELETE', '/projects/'.$foreign[1]->getId()->toString(), $this->securityKey('m-prj-del-own'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeletingProjectWithoutKeyIsRejected(): void
    {
        [, $project] = $this->seededPartnerWithProject('m-prj-no-key');

        $this->request('DELETE', '/projects/'.$project->getId()->toString());

        self::assertResponseStatusCodeSame(401);
    }

    // ---- RFC-002-7-3: Contractors ----------------------------------------------

    public function testContractorMutationCoversEveryScope(): void
    {
        $partner = $this->seededPartner('m-ctr-own');
        $contractor = $this->factory()->createContractor($partner, 'm-ctr-own');

        $foreignPartner = $this->seededPartner('m-ctr-for');
        $foreign = $this->factory()->createContractor($foreignPartner, 'm-ctr-for');
        $ownKey = $this->securityKey('m-ctr-own');

        $this->request('GET', '/contractors/'.$foreign->getId()->toString(), self::MASTER_KEY);
        self::assertResponseIsSuccessful();
        self::assertSame($foreign->getId()->toString(), $this->decode()['id']);

        $this->request('GET', '/contractors/'.$contractor->getId()->toString(), $ownKey);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/contractors/'.$foreign->getId()->toString(), $ownKey);
        self::assertResponseStatusCodeSame(404);

        $this->request('PATCH', '/contractors/'.$foreign->getId()->toString(), self::MASTER_KEY, ['name' => 'Master Contractor']);
        self::assertResponseIsSuccessful();

        $this->request('PATCH', '/contractors/'.$contractor->getId()->toString(), $ownKey, ['name' => 'Own Contractor']);
        self::assertResponseIsSuccessful();
        self::assertSame('Own Contractor', $this->decode()['name']);

        $this->request('PATCH', '/contractors/'.$foreign->getId()->toString(), $ownKey, ['name' => 'Hijacked Contractor']);
        self::assertResponseStatusCodeSame(404);

        $this->request('DELETE', '/contractors/'.$foreign->getId()->toString(), self::MASTER_KEY);
        self::assertResponseStatusCodeSame(204);

        $this->request('DELETE', '/contractors/'.$contractor->getId()->toString(), $ownKey);
        self::assertResponseStatusCodeSame(204);
    }

    public function testPartnerKeyCannotDeleteAnotherPartnerContractor(): void
    {
        $partner = $this->seededPartner('m-ctr-del-own');
        $this->factory()->createContractor($partner, 'm-ctr-del-own');

        $foreignPartner = $this->seededPartner('m-ctr-del-for');
        $foreign = $this->factory()->createContractor($foreignPartner, 'm-ctr-del-for');

        $this->request('DELETE', '/contractors/'.$foreign->getId()->toString(), $this->securityKey('m-ctr-del-own'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeletingContractorWithoutKeyIsRejected(): void
    {
        $partner = $this->seededPartner('m-ctr-no-key');
        $contractor = $this->factory()->createContractor($partner, 'm-ctr-no-key');

        $this->request('DELETE', '/contractors/'.$contractor->getId()->toString());

        self::assertResponseStatusCodeSame(401);
    }

    // ---- RFC-002-7-4: ApiKeys --------------------------------------------------

    public function testApiKeyMutationCoversEveryScope(): void
    {
        [, $project] = $this->seededPartnerWithProject('m-ak-own');
        $apiKey = $this->factory()->createApiKey($project, 'plain-m-apikey-own', 'm-ak-own');

        [, $foreignProject] = $this->seededPartnerWithProject('m-ak-for');
        $foreign = $this->factory()->createApiKey($foreignProject, 'plain-m-apikey-foreign', 'm-ak-for');
        $ownKey = $this->securityKey('m-ak-own');

        $this->request('GET', '/api-keys/'.$foreign->getId()->toString(), self::MASTER_KEY);
        self::assertResponseIsSuccessful();
        self::assertSame($foreign->getId()->toString(), $this->decode()['id']);

        $this->request('GET', '/api-keys/'.$apiKey->getId()->toString(), $ownKey);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/api-keys/'.$foreign->getId()->toString(), $ownKey);
        self::assertResponseStatusCodeSame(404);

        $this->request('PATCH', '/api-keys/'.$foreign->getId()->toString(), self::MASTER_KEY, ['name' => 'Master Key']);
        self::assertResponseIsSuccessful();

        $this->request('PATCH', '/api-keys/'.$apiKey->getId()->toString(), $ownKey, ['name' => 'Own Key']);
        self::assertResponseIsSuccessful();
        self::assertSame('Own Key', $this->decode()['name']);

        $this->request('PATCH', '/api-keys/'.$foreign->getId()->toString(), $ownKey, ['name' => 'Hijacked Key']);
        self::assertResponseStatusCodeSame(404);

        $this->request('DELETE', '/api-keys/'.$foreign->getId()->toString(), self::MASTER_KEY);
        self::assertResponseStatusCodeSame(204);

        $this->request('DELETE', '/api-keys/'.$apiKey->getId()->toString(), $ownKey);
        self::assertResponseStatusCodeSame(204);
    }

    public function testPartnerKeyCannotDeleteAnotherPartnerApiKey(): void
    {
        $this->seededPartnerWithProject('m-ak-del-own');
        [, $foreignProject] = $this->seededPartnerWithProject('m-ak-del-for');
        $foreign = $this->factory()->createApiKey($foreignProject, 'plain-m-apikey-del-foreign', 'm-ak-del-for');

        $this->request('DELETE', '/api-keys/'.$foreign->getId()->toString(), $this->securityKey('m-ak-del-own'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeletingApiKeyWithoutKeyIsRejected(): void
    {
        [, $project] = $this->seededPartnerWithProject('m-ak-no-key');
        $apiKey = $this->factory()->createApiKey($project, 'plain-m-apikey-no-key', 'm-ak-no-key');

        $this->request('DELETE', '/api-keys/'.$apiKey->getId()->toString());

        self::assertResponseStatusCodeSame(401);
    }

    // ---- RFC-002-7-5: Users ----------------------------------------------------

    public function testUserMutationCoversEveryScope(): void
    {
        $partner = $this->seededPartner('m-usr-own');
        $contractor = $this->factory()->createContractor($partner, 'm-usr-own');
        $user = $this->factory()->createUser($contractor, 'plain-password', 'm-usr-own');

        $foreignPartner = $this->seededPartner('m-usr-for');
        $foreignContractor = $this->factory()->createContractor($foreignPartner, 'm-usr-for');
        $foreign = $this->factory()->createUser($foreignContractor, 'plain-password', 'm-usr-for');
        $ownKey = $this->securityKey('m-usr-own');

        $this->request('GET', '/users/'.$foreign->getId()->toString(), self::MASTER_KEY);
        self::assertResponseIsSuccessful();
        self::assertSame($foreign->getId()->toString(), $this->decode()['id']);

        $this->request('GET', '/users/'.$user->getId()->toString(), $ownKey);
        self::assertResponseIsSuccessful();

        $this->request('GET', '/users/'.$foreign->getId()->toString(), $ownKey);
        self::assertResponseStatusCodeSame(404);

        $this->request('PATCH', '/users/'.$foreign->getId()->toString(), self::MASTER_KEY, ['username' => 'masteruser']);
        self::assertResponseIsSuccessful();

        $this->request('PATCH', '/users/'.$user->getId()->toString(), $ownKey, ['username' => 'ownuser']);
        self::assertResponseIsSuccessful();
        self::assertSame('ownuser', $this->decode()['username']);

        $this->request('PATCH', '/users/'.$foreign->getId()->toString(), $ownKey, ['username' => 'hijackeduser']);
        self::assertResponseStatusCodeSame(404);

        $this->request('DELETE', '/users/'.$foreign->getId()->toString(), self::MASTER_KEY);
        self::assertResponseStatusCodeSame(204);

        $this->request('DELETE', '/users/'.$user->getId()->toString(), $ownKey);
        self::assertResponseStatusCodeSame(204);
    }

    public function testPartnerKeyCannotDeleteAnotherPartnerUser(): void
    {
        $partner = $this->seededPartner('m-usr-del-own');
        $contractor = $this->factory()->createContractor($partner, 'm-usr-del-own');
        $this->factory()->createUser($contractor, 'plain-password', 'm-usr-del-own');

        $foreignPartner = $this->seededPartner('m-usr-del-for');
        $foreignContractor = $this->factory()->createContractor($foreignPartner, 'm-usr-del-for');
        $foreign = $this->factory()->createUser($foreignContractor, 'plain-password', 'm-usr-del-for');

        $this->request('DELETE', '/users/'.$foreign->getId()->toString(), $this->securityKey('m-usr-del-own'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeletingUserWithoutKeyIsRejected(): void
    {
        $partner = $this->seededPartner('m-usr-no-key');
        $contractor = $this->factory()->createContractor($partner, 'm-usr-no-key');
        $user = $this->factory()->createUser($contractor, 'plain-password', 'm-usr-no-key');

        $this->request('DELETE', '/users/'.$user->getId()->toString());

        self::assertResponseStatusCodeSame(401);
    }

    // ---- Helpers ---------------------------------------------------------------

    /**
     * @return array{0: Partner, 1: Project, 2: ApiKey}
     */
    private function seededPartnerWithProject(string $suffix): array
    {
        $partner = $this->factory()->createPartner($suffix);
        $project = $this->factory()->createProject($partner, $suffix);
        $apiKey = $this->factory()->createApiKey($project, $this->securityKey($suffix), $suffix);

        return [$partner, $project, $apiKey];
    }

    private function seededPartner(string $suffix): Partner
    {
        $partner = $this->factory()->createPartner($suffix);
        $project = $this->factory()->createProject($partner, $suffix);
        $this->factory()->createApiKey($project, $this->securityKey($suffix), $suffix);

        return $partner;
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function request(string $method, string $uri, ?string $securityKey = null, ?array $body = null): void
    {
        $server = [];

        if (null !== $securityKey) {
            $server['HTTP_X_SECURITY_KEY'] = $securityKey;
        }

        if (null !== $body) {
            $server['CONTENT_TYPE'] = 'PATCH' === $method ? 'application/merge-patch+json' : 'application/json';
        }

        $this->client->request($method, $uri, [], [], $server, null === $body ? null : json_encode($body, \JSON_THROW_ON_ERROR));
    }

    private function securityKey(string $suffix): string
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
}
