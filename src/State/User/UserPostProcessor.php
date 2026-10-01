<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\User\UserInput;
use Phprise\KoenmaID\ApiResource\User\UserOutput;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Phprise\KoenmaID\Service\User\UserRegistrar;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<UserInput, UserOutput>
 */
final readonly class UserPostProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRegistrar $registrar,
        private ContractorRepository $contractors,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        if (!$data instanceof UserInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $contractor = $this->resolveContractor($uriVariables['contractorId'] ?? null);
        $user = $this->registrar->register($contractor, $data);

        return UserOutput::fromEntity($user);
    }

    private function resolveContractor(mixed $contractorId): Contractor
    {
        $contractor = $this->contractors->find($contractorId);

        if (!$contractor instanceof Contractor) {
            throw new NotFoundHttpException('Contractor not found.');
        }

        return $contractor;
    }
}
