<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Partner;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\ApiResource\Partner\PartnerOutput;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<PartnerOutput>
 */
final readonly class PartnerItemProvider implements ProviderInterface
{
    public function __construct(private PartnerRepository $partners)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PartnerOutput
    {
        $partner = $this->partners->find($uriVariables['id'] ?? null);

        if (!$partner instanceof Partner || null !== $partner->deletedAt()) {
            throw new NotFoundHttpException('Partner not found.');
        }

        return PartnerOutput::fromEntity($partner);
    }
}
