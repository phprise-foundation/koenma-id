<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Project;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<Project>
 */
final readonly class CreateProvider implements ProviderInterface
{
    public function __construct(private PartnerRepository $partners)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Project
    {
        $partner = $this->partners->find($uriVariables['partnerId'] ?? null);

        if (!$partner instanceof Partner || null !== $partner->getDeletedAt()) {
            throw new NotFoundHttpException('Partner not found.');
        }

        return (new Project())->setPartner($partner);
    }
}
