<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Service;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Service\Security\SecurityKeyContext;
use Phprise\KoenmaID\Service\Security\SecurityKeyType;
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
        self::assertSame($partner->getId()->toString(), $scope->partnerId()?->toString());
    }

    public function testExposesThePersistedPartnerEntity(): void
    {
        $partner = $this->factory()->createPartner('ctx-partner');
        $project = $this->factory()->createProject($partner, 'ctx-partner');
        $this->factory()->createApiKey($project, 'sk_PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP', 'ctx-partner');

        $this->pushRequest('sk_PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPP');

        $scope = $this->context()->scope();
        $exposed = $scope->getPartner();

        self::assertInstanceOf(Partner::class, $exposed);
        self::assertSame($partner->getId()->toString(), $exposed->getId()?->toString());
        self::assertSame($partner->getName(), $exposed->getName());
        self::assertSame(SecurityKeyType::Partner, $this->context()->keyType());
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

    public function testKeyTypeIsAnonymousWhenHeaderIsAbsent(): void
    {
        $this->pushRequest(null);

        self::assertSame(SecurityKeyType::Anonymous, $this->context()->keyType());
    }

    public function testKeyTypeIsMasterWhenMasterKeyIsUsed(): void
    {
        $this->pushRequest(self::MASTER_KEY);

        self::assertSame(SecurityKeyType::Master, $this->context()->keyType());
    }

    public function testKeyTypeIsPartnerForValidApiKey(): void
    {
        $partner = $this->factory()->createPartner('keytype-partner');
        $project = $this->factory()->createProject($partner, 'keytype-partner');
        $this->factory()->createApiKey($project, 'sk_KKKKKKKKKKKKKKKKKKKKKKKKKKKKKKKK', 'keytype-partner');

        $this->pushRequest('sk_KKKKKKKKKKKKKKKKKKKKKKKKKKKKKKKK');

        self::assertSame(SecurityKeyType::Partner, $this->context()->keyType());
    }

    public function testKeyTypeIsAnonymousForUnknownKey(): void
    {
        $this->pushRequest('sk_UUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU');

        self::assertSame(SecurityKeyType::Anonymous, $this->context()->keyType());
    }

    public function testKeyTypeIsAnonymousForMalformedKey(): void
    {
        $this->pushRequest('not-a-key');

        self::assertSame(SecurityKeyType::Anonymous, $this->context()->keyType());
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
