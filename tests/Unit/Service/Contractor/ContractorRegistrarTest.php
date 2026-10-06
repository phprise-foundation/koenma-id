<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Contractor;

use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Phprise\KoenmaID\Service\Contractor\ContractorRegistrar;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ContractorRegistrarTest extends TestCase
{
    public function testRegisterThrowsConflictHttpExceptionWhenDocumentExists(): void
    {
        $contractorRepository = $this->createMock(ContractorRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $contractorRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['document' => '12345678901234567890123456789012'])
            ->willReturn(new Contractor());

        $registrar = new ContractorRegistrar($contractorRepository, $entityManager);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Document already registered.');

        $registrar->register($this->contractor());
    }

    public function testRegisterSucceedsWhenDocumentDoesNotExist(): void
    {
        $contractorRepository = $this->createMock(ContractorRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $contractorRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['document' => '12345678901234567890123456789012'])
            ->willReturn(null);

        $contractor = $this->contractor();

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($contractor);

        $entityManager->expects($this->once())
            ->method('flush');

        $registrar = new ContractorRegistrar($contractorRepository, $entityManager);

        $result = $registrar->register($contractor);

        $this->assertSame($contractor, $result);
        $this->assertSame('Test Name', $result->getName());
        $this->assertSame('12345678901234567890123456789012', $result->getDocument());
    }

    private function contractor(): Contractor
    {
        $partner = new Partner('Test', 'test@example.com', '1234567890');

        return (new Contractor())
            ->setPartner($partner)
            ->setName('Test Name')
            ->setDocument('12345678901234567890123456789012');
    }
}
