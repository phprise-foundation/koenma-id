<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Api;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PartnerSegregationListingTest extends WebTestCase
{
    private const string MASTER_KEY = 'sk_MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testMasterKeyListsEveryPartner(): void
    {
        $first = $this->factory()->createPartner('seg-master-a');
        $second = $this->factory()->createPartner('seg-master-b');

        $this->get('/partners', self::MASTER_KEY);

        self::assertResponseIsSuccessful();
        $ids = array_column($this->decodeCollection(), 'id');
        self::assertContains($first->getId()->toString(), $ids);
        self::assertContains($second->getId()->toString(), $ids);
    }

    public function testPartnerKeyListsOnlyItsOwnPartner(): void
    {
        [$owned] = $this->partnerWithKey('seg-own-partner');
        $this->factory()->createPartner('seg-other-partner');

        $this->get('/partners', $this->securityKey('seg-own-partner'));

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($owned->getId()->toString(), $payload[0]['id']);
    }

    public function testMasterKeyListsProjectsOfAnyPartner(): void
    {
        $partner = $this->factory()->createPartner('seg-master-project');
        $this->factory()->createProject($partner, 'one');
        $this->factory()->createProject($partner, 'two');

        $this->get('/partners/'.$partner->getId()->toString().'/projects', self::MASTER_KEY);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $this->decodeCollection());
    }

    public function testPartnerKeyListsOnlyItsOwnProjects(): void
    {
        [$partner, $project] = $this->partnerWithKey('seg-own-project');

        $other = $this->factory()->createPartner('seg-other-project');
        $this->factory()->createProject($other, 'foreign');

        $this->get('/partners/'.$partner->getId()->toString().'/projects', $this->securityKey('seg-own-project'));

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($project->getId()->toString(), $payload[0]['id']);
        self::assertSame($partner->getId()->toString(), $payload[0]['partnerId']);
    }

    public function testPartnerKeyCannotListProjectsOfAnotherPartner(): void
    {
        $this->partnerWithKey('seg-project-key');
        $foreign = $this->factory()->createPartner('seg-proj-foreign');
        $this->factory()->createProject($foreign, 'foreign');

        $this->get('/partners/'.$foreign->getId()->toString().'/projects', $this->securityKey('seg-project-key'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testPartnerKeyListsOnlyItsOwnContractors(): void
    {
        [$partner] = $this->partnerWithKey('seg-own-contractor');
        $contractor = $this->factory()->createContractor($partner, 'owned');

        $this->get('/partners/'.$partner->getId()->toString().'/contractors', $this->securityKey('seg-own-contractor'));

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($contractor->getId()->toString(), $payload[0]['id']);
    }

    public function testPartnerKeyCannotListContractorsOfAnotherPartner(): void
    {
        $this->partnerWithKey('seg-contractor-key');
        $foreign = $this->factory()->createPartner('seg-ctr-foreign');
        $this->factory()->createContractor($foreign, 'foreign');

        $this->get('/partners/'.$foreign->getId()->toString().'/contractors', $this->securityKey('seg-contractor-key'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testPartnerKeyListsOnlyUsersOfItsOwnContractor(): void
    {
        [$partner] = $this->partnerWithKey('seg-own-user');
        $contractor = $this->factory()->createContractor($partner, 'owned');
        $this->factory()->createUser($contractor, 'plain-password', 'owned');
        $this->factory()->createUser($contractor, 'plain-password', 'owned-two');

        $this->get('/contractors/'.$contractor->getId()->toString().'/users', $this->securityKey('seg-own-user'));

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(2, $payload);
        self::assertSame($contractor->getId()->toString(), $payload[0]['contractorId']);
    }

    public function testPartnerKeyCannotListUsersOfAnotherPartnerContractor(): void
    {
        $this->partnerWithKey('seg-user-key');
        $foreign = $this->factory()->createPartner('seg-user-foreign');
        $foreignContractor = $this->factory()->createContractor($foreign, 'foreign');
        $this->factory()->createUser($foreignContractor, 'plain-password', 'foreign');

        $this->get('/contractors/'.$foreignContractor->getId()->toString().'/users', $this->securityKey('seg-user-key'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testPartnerKeyListsOnlyApiKeysOfItsOwnProject(): void
    {
        [, $project] = $this->partnerWithKey('seg-own-api-key');
        $this->factory()->createApiKey($project, 'plain-owned-extra', 'extra');

        $this->get('/projects/'.$project->getId()->toString().'/api-keys', $this->securityKey('seg-own-api-key'));

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(2, $payload);
        self::assertSame($project->getId()->toString(), $payload[0]['projectId']);
    }

    public function testPartnerKeyCannotListApiKeysOfAnotherPartnerProject(): void
    {
        $this->partnerWithKey('seg-api-key-key');
        $foreign = $this->factory()->createPartner('seg-keys-foreign');
        $foreignProject = $this->factory()->createProject($foreign, 'foreign');
        $this->factory()->createApiKey($foreignProject, 'plain-foreign', 'foreign');

        $this->get('/projects/'.$foreignProject->getId()->toString().'/api-keys', $this->securityKey('seg-api-key-key'));

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @return array{0: Partner, 1: Project}
     */
    private function partnerWithKey(string $suffix): array
    {
        $partner = $this->factory()->createPartner($suffix);
        $project = $this->factory()->createProject($partner, $suffix);
        $this->factory()->createApiKey($project, $this->securityKey($suffix), $suffix);

        return [$partner, $project];
    }

    private function get(string $uri, string $securityKey): void
    {
        $this->client->request('GET', $uri, [], [], [
            'HTTP_X_SECURITY_KEY' => $securityKey,
        ]);
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
     * @return list<array<string, mixed>>
     */
    private function decodeCollection(): array
    {
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        return $payload['member'];
    }
}
