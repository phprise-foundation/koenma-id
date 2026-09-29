<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Contractor\ContractorInput;
use Phprise\KoenmaID\ApiResource\Contractor\ContractorOutput;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Phprise\KoenmaID\Service\Contractor\ContractorRegistrar;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ContractorInput, ContractorOutput>
 */
final readonly class ContractorPostProcessor implements ProcessorInterface
{
    public function __construct(
        private PartnerRepository $partners,
        private ContractorRegistrar $registrar,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ContractorOutput
    {
        if (!$data instanceof ContractorInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $partner = $this->partners->find($uriVariables['partnerId'] ?? null);

        if (!$partner instanceof Partner || null !== $partner->deletedAt()) {
            throw new NotFoundHttpException('Partner not found.');
        }

        $contractor = $this->registrar->register($partner, $data->name, $data->document);

        return ContractorOutput::fromEntity($contractor);
    }
}
