<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Partner;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class PartnerRegistrar
{
    public function __construct(
        private PartnerRepository $partners,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function register(string $name, string $emailAddress, string $document): Partner
    {
        $this->assertEmailIsAvailable($emailAddress);
        $this->assertDocumentIsAvailable($document);

        $partner = new Partner($name, $emailAddress, $document);

        $this->entityManager->persist($partner);
        $this->entityManager->flush();

        return $partner;
    }

    private function assertEmailIsAvailable(string $emailAddress): void
    {
        if (null !== $this->partners->findOneBy(['emailAddress' => $emailAddress])) {
            throw new ConflictHttpException('Email address already registered.');
        }
    }

    private function assertDocumentIsAvailable(string $document): void
    {
        if (null !== $this->partners->findOneBy(['document' => $document])) {
            throw new ConflictHttpException('Document already registered.');
        }
    }
}
