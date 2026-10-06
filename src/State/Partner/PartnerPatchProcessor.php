<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Partner;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Service\Security\MasterScopeGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<Partner, Partner>
 */
final readonly class PartnerPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MasterScopeGuard $masterScopeGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Partner
    {
        if (!$data instanceof Partner) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $this->masterScopeGuard->assertMaster();
        $this->entityManager->flush();

        return $data;
    }
}
