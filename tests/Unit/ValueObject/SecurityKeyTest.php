<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\ValueObject;

use Phprise\KoenmaID\ValueObject\SecurityKey;
use PHPUnit\Framework\TestCase;

final class SecurityKeyTest extends TestCase
{
    public function testAcceptsValidKey(): void
    {
        $key = SecurityKey::fromString('sk_9OqPu4m0VRfEwPc9x6fRejjQEz775exr');

        self::assertSame('sk_9OqPu4m0VRfEwPc9x6fRejjQEz775exr', $key->toString());
    }

    public function testHashesWithSha256(): void
    {
        $key = SecurityKey::fromString('sk_9OqPu4m0VRfEwPc9x6fRejjQEz775exr');

        self::assertSame(hash('sha256', 'sk_9OqPu4m0VRfEwPc9x6fRejjQEz775exr'), $key->hash());
    }

    public function testRejectsMissingPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SecurityKey::fromString('9OqPu4m0VRfEwPc9x6fRejjQEz775exr');
    }

    public function testRejectsShortSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SecurityKey::fromString('sk_short');
    }

    public function testRejectsNonAlphanumericSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SecurityKey::fromString('sk_9OqPu4m0VRfEwPc9x6fRejjQEz775ex!');
    }
}
