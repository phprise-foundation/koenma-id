<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\User\UserInput;
use Phprise\KoenmaID\ApiResource\User\UserOutput;
use Phprise\KoenmaID\Service\User\UserRegistrar;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<UserInput, UserOutput>
 */
final readonly class UserPostProcessor implements ProcessorInterface
{
    public function __construct(private UserRegistrar $registrar)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserOutput
    {
        if (!$data instanceof UserInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $user = $this->registrar->register($data);

        return UserOutput::fromEntity($user);
    }
}
