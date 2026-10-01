<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Service;

use Phprise\KoenmaID\Service\Security\SecurityKeyContext;
use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class SecurityKeyContextTest extends KernelTestCase
{
    private const string MASTER_KEY = 'sk_MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';

    public function testResolvesAnonymousWhenHeaderIsAbsent(): void
    {
        $this->pushRequest(null);

        self::assertTrue($this->context()->scope()->isAnonymous());
    }

    public function testResolvesMasterWhenMasterKeyIsUsed(): void
    {
        $this->pushRequest(self::MASTER_KEY);

        self::assertTrue($this->context()->scope()->isMaster());
    }

    public function testResolvesPartnerForValidApiKey(): void
    {
        $partner = $this->factory()->createPartner('ctx-valid');
        $project = $this->factory()->createProject($partner, 'ctx-valid');
        $this->factory()->createApiKey($project, 'sk_VVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV', 'ctx-valid');

        $this->pushRequest('sk_VVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV');

        $scope = $this->context()->scope();
        self::assertFalse($scope->isMaster());
        self::assertFalse($scope->isAnonymous());
        self::assertSame($partner->id()->toString(), $scope->partnerId()?->toString());
    }

    public function testResolvesAnonymousForUnknownKey(): void
    {
        $this->pushRequest('sk_UUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU');

        self::assertTrue($this->context()->scope()->isAnonymous());
    }

    public function testResolvesAnonymousForMalformedKey(): void
    {
        $this->pushRequest('not-a-key');

        self::assertTrue($this->context()->scope()->isAnonymous());
    }

    private function pushRequest(?string $securityKey): void
    {
        $stack = $this->requestStack();
        while (null !== $stack->getCurrentRequest()) {
            $stack->pop();
        }

        $request = new Request();
        if (null !== $securityKey) {
            $request->headers->set(SecurityKeyContext::HEADER, $securityKey);
        }

        $stack->push($request);
    }

    private function context(): SecurityKeyContext
    {
        return static::getContainer()->get(SecurityKeyContext::class);
    }

    private function requestStack(): RequestStack
    {
        return static::getContainer()->get('request_stack');
    }

    private function factory(): TestDataFactory
    {
        return static::getContainer()->get(TestDataFactory::class);
    }
}
