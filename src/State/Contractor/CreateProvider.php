<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Contractor>
 */
final readonly class CreateProvider implements ProviderInterface
{
    public function __construct(private PartnerRepository $partners)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Contractor
    {
        $partner = $this->partners->find($uriVariables['partnerId'] ?? null);

        if (!$partner instanceof Partner || null !== $partner->getDeletedAt()) {
            throw new NotFoundHttpException('Partner not found.');
        }

        return (new Contractor())->setPartner($partner);
    }
}
