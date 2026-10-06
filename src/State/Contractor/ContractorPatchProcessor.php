<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Contractor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\Contractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<Contractor, Contractor>
 */
final readonly class ContractorPatchProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Contractor
    {
        if (!$data instanceof Contractor) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $this->entityManager->flush();

        return $data;
    }
}
