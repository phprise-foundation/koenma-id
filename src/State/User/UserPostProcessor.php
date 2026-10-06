<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Service\User\UserRegistrar;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<User, User>
 */
final readonly class UserPostProcessor implements ProcessorInterface
{
    public function __construct(private UserRegistrar $registrar)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (!$data instanceof User) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        return $this->registrar->register($data);
    }
}
