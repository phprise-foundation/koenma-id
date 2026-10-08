<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ScopedContractorLookup
{
    public function __construct(
        private ContractorRepository $contractors,
        private ScopeGuard $guard,
    ) {
    }

    public function requireVisible(mixed $id): Contractor
    {
        $contractor = $this->contractors->find($id);

        if (!$contractor instanceof Contractor || null !== $contractor->getDeletedAt()) {
            throw new NotFoundHttpException('Contractor not found.');
        }

        $partner = $contractor->getPartner();

        if (!$partner instanceof Partner || !$this->guard->allows($partner)) {
            throw new NotFoundHttpException('Contractor not found.');
        }

        return $contractor;
    }
}
