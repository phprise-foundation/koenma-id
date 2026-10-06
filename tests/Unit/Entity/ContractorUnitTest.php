<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Entity;

use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\Partner;
use PHPUnit\Framework\TestCase;

final class ContractorUnitTest extends TestCase
{
    public function testSetPartnerIsFluent(): void
    {
        $partner = new Partner('Test Partner', 'test@example.com', '1234567890');
        $contractor = new Contractor();

        self::assertSame($contractor, $contractor->setPartner($partner));
        self::assertSame($partner, $contractor->getPartner());
    }

    public function testSetNameAndDocumentAreFluent(): void
    {
        $contractor = new Contractor();

        self::assertSame($contractor, $contractor->setName('Contractor Alpha'));
        self::assertSame($contractor, $contractor->setDocument('12345678901234567890123456789012'));
        self::assertSame('Contractor Alpha', $contractor->getName());
        self::assertSame('12345678901234567890123456789012', $contractor->getDocument());
    }
}
