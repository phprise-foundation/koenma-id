<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\Partner;

final readonly class ScopeGuard
{
    public function __construct(private SecurityScopeProvider $scopeProvider)
    {
    }

    public function allows(Partner $partner): bool
    {
        $scopedPartner = $this->scopedPartner();

        if (null === $scopedPartner) {
            return true;
        }

        return $this->isSamePartner($partner, $scopedPartner);
    }

    public function scopedPartner(): ?Partner
    {
        return $this->scopeProvider->scope()->getPartner();
    }

    private function isSamePartner(Partner $subject, Partner $partner): bool
    {
        $subjectId = $subject->getId();
        $partnerId = $partner->getId();

        if (null === $subjectId || null === $partnerId) {
            return false;
        }

        return $subjectId->equals($partnerId);
    }
}
