<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Partner;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Partner\PartnerOutput;
use Phprise\KoenmaID\ApiResource\Partner\PartnerPatchInput;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<PartnerPatchInput, PartnerOutput>
 */
final readonly class PartnerPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private PartnerRepository $partners,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PartnerOutput
    {
        if (!$data instanceof PartnerPatchInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $partner = $this->partners->find($uriVariables['id'] ?? null);

        if (!$partner instanceof Partner || null !== $partner->deletedAt()) {
            throw new NotFoundHttpException('Partner not found.');
        }

        if (null !== $data->name) {
            $partner->rename($data->name);
        }

        if (null !== $data->emailAddress) {
            $partner->changeEmailAddress($data->emailAddress);
        }

        $this->entityManager->flush();

        return PartnerOutput::fromEntity($partner);
    }
}
