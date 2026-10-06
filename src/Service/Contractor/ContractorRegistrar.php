<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Contractor;

use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class ContractorRegistrar
{
    public function __construct(
        private ContractorRepository $contractors,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function register(Contractor $contractor): Contractor
    {
        $this->assertDocumentIsAvailable($contractor->getDocument());

        $this->entityManager->persist($contractor);
        $this->entityManager->flush();

        return $contractor;
    }

    private function assertDocumentIsAvailable(string $document): void
    {
        if (null !== $this->contractors->findOneBy(['document' => $document])) {
            throw new ConflictHttpException('Document already registered.');
        }
    }
}
