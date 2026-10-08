<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Service\Security\ScopeGuard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<User, null>
 */
final readonly class UserDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScopeGuard $scopeGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$data instanceof User) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $partner = $data->getContractor()?->getPartner();

        if (!$partner instanceof Partner) {
            throw new NotFoundHttpException('User not found.');
        }

        $this->scopeGuard->assertCanWrite($partner, 'User not found.');

        $data->delete();
        $this->entityManager->flush();

        return null;
    }
}
