<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Partner;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Partner\PartnerInput;
use Phprise\KoenmaID\ApiResource\Partner\PartnerOutput;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Service\Partner\PartnerRegistrar;
use Phprise\KoenmaID\Service\Security\MasterScopeGuard;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<PartnerInput, PartnerOutput>
 */
final readonly class PartnerPostProcessor implements ProcessorInterface
{
    public function __construct(
        private PartnerRegistrar $registrar,
        private MasterScopeGuard $masterScopeGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PartnerOutput
    {
        if (!$data instanceof PartnerInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $this->masterScopeGuard->assertMaster();

        $partner = $this->registrar->register($data->name, $data->emailAddress, $data->document);

        return PartnerOutput::fromEntity($partner);
    }
}
