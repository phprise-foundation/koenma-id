<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ScopedPartnerLookup
{
    public function __construct(
        private PartnerRepository $partners,
        private ScopeGuard $guard,
    ) {
    }

    /**
     * @return list<Partner>
     */
    public function visible(): array
    {
        $scopedPartner = $this->guard->scopedPartner();

        if (null === $scopedPartner) {
            return $this->partners->findBy(['deletedAt' => null]);
        }

        if (null !== $scopedPartner->getDeletedAt()) {
            return [];
        }

        return [$scopedPartner];
    }

    public function requireVisible(mixed $id): Partner
    {
        $partner = $this->partners->find($id);

        if (!$partner instanceof Partner || null !== $partner->getDeletedAt()) {
            throw new NotFoundHttpException('Partner not found.');
        }

        if (!$this->guard->allows($partner)) {
            throw new NotFoundHttpException('Partner not found.');
        }

        return $partner;
    }
}
