<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Api;

use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ContractorApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCreatesContractorUnderPartner(): void
    {
        $partner = $this->factory()->createPartner('ctr-create');

        $this->client->request('POST', '/partners/'.$partner->id()->toString().'/contractors', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Contractor Alpha',
            'document' => 'doc-ctr-alpha',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(201);
        $payload = $this->decode();
        self::assertSame('Contractor Alpha', $payload['name']);
        self::assertSame('doc-ctr-alpha', $payload['document']);
        self::assertSame($partner->id()->toString(), $payload['partnerId']);
    }

    public function testRejectsInvalidContractorPayload(): void
    {
        $partner = $this->factory()->createPartner('ctr-invalid');

        $this->client->request('POST', '/partners/'.$partner->id()->toString().'/contractors', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => '', 'document' => ''], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(422);
    }

    public function testReturnsNotFoundForUnknownPartner(): void
    {
        $this->client->request('POST', '/partners/prt_01M2BW4T17D5EG03XCJ8XG0ARR/contractors', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Orphan', 'document' => 'doc-orphan'], \JSON_THROW_ON_ERROR));

        self::assertResponseStatusCodeSame(404);
    }

    public function testListsContractorsOfPartner(): void
    {
        $partner = $this->factory()->createPartner('ctr-list');
        $this->factory()->createContractor($partner, 'list-a');
        $this->factory()->createContractor($partner, 'list-b');

        $this->client->request('GET', '/partners/'.$partner->id()->toString().'/contractors');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(2, $payload);
    }

    public function testListsOnlyContractorsOfGivenPartner(): void
    {
        $partner = $this->factory()->createPartner('ctr-scope-a');
        $other = $this->factory()->createPartner('ctr-scope-b');
        $this->factory()->createContractor($partner, 'scope-a');
        $this->factory()->createContractor($other, 'scope-b');

        $this->client->request('GET', '/partners/'.$partner->id()->toString().'/contractors');

        self::assertResponseIsSuccessful();
        $payload = $this->decodeCollection();
        self::assertCount(1, $payload);
        self::assertSame($partner->id()->toString(), $payload[0]['partnerId']);
    }

    public function testGetsContractorById(): void
    {
        $partner = $this->factory()->createPartner('ctr-get');
        $contractor = $this->factory()->createContractor($partner, 'get');

        $this->client->request('GET', '/contractors/'.$contractor->id()->toString());

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame($contractor->id()->toString(), $payload['id']);
    }

    public function testPatchesContractor(): void
    {
        $partner = $this->factory()->createPartner('ctr-patch');
        $contractor = $this->factory()->createContractor($partner, 'patch');

        $this->client->request('PATCH', '/contractors/'.$contractor->id()->toString(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode(['name' => 'Renamed Contractor'], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        $payload = $this->decode();
        self::assertSame('Renamed Contractor', $payload['name']);
    }

    public function testReturnsNotFoundForUnknownContractor(): void
    {
        $this->client->request('GET', '/contractors/cnt_01M2BW4T17D5EG03XCJ8XG0ARR');

        self::assertResponseStatusCodeSame(404);
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
