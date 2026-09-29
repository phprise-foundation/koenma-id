<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\ValueObject;

use Phprise\KoenmaID\ValueObject\ApiKeyId;
use Phprise\KoenmaID\ValueObject\ContractorId;
use Phprise\KoenmaID\ValueObject\PartnerId;
use Phprise\KoenmaID\ValueObject\ProjectId;
use Phprise\KoenmaID\ValueObject\RefreshTokenId;
use Phprise\KoenmaID\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class PrefixedIdTest extends TestCase
{
    public function testGeneratesWithExpectedPrefix(): void
    {
        self::assertStringStartsWith('prt_', PartnerId::generate()->toString());
        self::assertStringStartsWith('prj_', ProjectId::generate()->toString());
        self::assertStringStartsWith('aky_', ApiKeyId::generate()->toString());
        self::assertStringStartsWith('cnt_', ContractorId::generate()->toString());
        self::assertStringStartsWith('usr_', UserId::generate()->toString());
        self::assertStringStartsWith('rtk_', RefreshTokenId::generate()->toString());
    }

    public function testRoundTripPreservesIdentity(): void
    {
        $original = PartnerId::generate();
        $parsed = PartnerId::fromString($original->toString());

        self::assertTrue($original->equals($parsed));
        self::assertSame($original->toString(), $parsed->toString());
    }

    public function testRejectsWrongPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UserId::fromString(PartnerId::generate()->toString());
    }

    public function testRejectsMissingPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PartnerId::fromString('01M2BW4T17D5EG03XCJ8XG0ARR');
    }

    public function testDistinctInstancesAreNotEqual(): void
    {
        self::assertFalse(PartnerId::generate()->equals(PartnerId::generate()));
    }
}
