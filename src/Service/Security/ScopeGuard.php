<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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

    /**
     * Ensures the current scope may mutate a resource owned by the given partner.
     *
     * The master key may mutate anything and an anonymous scope is rejected with
     * 401. A partner key may only mutate resources of its own partner; anything
     * else is hidden with 404 so foreign resources stay unobservable.
     */
    public function assertCanWrite(Partner $owner, string $notFoundMessage): void
    {
        $scope = $this->scopeProvider->scope();

        if ($scope->isMaster()) {
            return;
        }

        if ($scope->isAnonymous()) {
            throw new UnauthorizedHttpException('SecurityKey', 'A valid security key is required.');
        }

        $scopedPartner = $scope->getPartner();

        if (null === $scopedPartner || !$this->isSamePartner($owner, $scopedPartner)) {
            throw new NotFoundHttpException($notFoundMessage);
        }
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
