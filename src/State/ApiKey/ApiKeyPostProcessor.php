<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\ApiKey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Service\ApiKey\ApiKeyIssuer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<ApiKey, ApiKey>
 */
final readonly class ApiKeyPostProcessor implements ProcessorInterface
{
    public function __construct(private ApiKeyIssuer $issuer)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ApiKey
    {
        if (!$data instanceof ApiKey || null === $data->getProject()) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $issued = $this->issuer->issue($data->getProject(), $data->getName(), $data->getExpiresInDays());

        $issued->apiKey->setSecurityKey($issued->plainKey);

        return $issued->apiKey;
    }
}
