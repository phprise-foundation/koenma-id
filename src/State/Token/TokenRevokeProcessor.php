<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Token;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Token\TokenRevokeInput;
use Phprise\KoenmaID\ApiResource\Token\TokenRevokeOutput;
use Phprise\KoenmaID\Service\Token\TokenRevoker;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<TokenRevokeInput, TokenRevokeOutput>
 */
final readonly class TokenRevokeProcessor implements ProcessorInterface
{
    public function __construct(private TokenRevoker $revoker)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TokenRevokeOutput
    {
        if (!$data instanceof TokenRevokeInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $this->revoker->revoke($data->refreshToken);

        $output = new TokenRevokeOutput();
        $output->revoked = true;

        return $output;
    }
}
