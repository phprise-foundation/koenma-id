<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MultiTenantAuthorizationVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(
        private readonly SecurityScopeProvider $scopeProvider,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        return null === $subject
            || $subject instanceof Partner
            || $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $scope = $this->scopeProvider->scope();

        if ($scope->isMaster()) {
            return true;
        }

        $partner = $scope->getPartner();

        if (null === $partner) {
            return false;
        }

        if (null === $subject) {
            return true;
        }

        if ($subject instanceof Partner) {
            return $this->isSamePartner($subject, $partner);
        }

        if ($subject instanceof Project) {
            return $this->isProjectInPartner($subject, $partner);
        }

        return false;
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

    private function isProjectInPartner(Project $project, Partner $partner): bool
    {
        return $this->isSamePartner($project->getPartner(), $partner);
    }
}
