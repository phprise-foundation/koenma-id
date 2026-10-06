<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<ApiKey, ApiKey>
 */
final readonly class ApiKeyPatchProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ApiKey
    {
        if (!$data instanceof ApiKey) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $this->entityManager->flush();

        return $data;
    }
}
