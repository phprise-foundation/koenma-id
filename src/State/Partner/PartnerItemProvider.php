<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Partner;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Service\Security\ScopedPartnerLookup;

/**
 * @implements ProviderInterface<Partner>
 */
final readonly class PartnerItemProvider implements ProviderInterface
{
    public function __construct(private ScopedPartnerLookup $partners)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Partner
    {
        return $this->partners->requireVisible($uriVariables['id'] ?? null);
    }
}
