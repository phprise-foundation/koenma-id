<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Contractor\ContractorOutput;
use Phprise\KoenmaID\ApiResource\Contractor\ContractorPatchInput;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<ContractorPatchInput, ContractorOutput>
 */
final readonly class ContractorPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private ContractorRepository $contractors,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ContractorOutput
    {
        if (!$data instanceof ContractorPatchInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $contractor = $this->contractors->find($uriVariables['id'] ?? null);

        if (!$contractor instanceof Contractor || null !== $contractor->deletedAt()) {
            throw new NotFoundHttpException('Contractor not found.');
        }

        if (null !== $data->name) {
            $contractor->rename($data->name);
        }

        $this->entityManager->flush();

        return ContractorOutput::fromEntity($contractor);
    }
}
