<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Token;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Token\TokenVerifyInput;
use Phprise\KoenmaID\ApiResource\Token\TokenVerifyOutput;
use Phprise\KoenmaID\Service\Token\TokenVerifier;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<TokenVerifyInput, TokenVerifyOutput>
 */
final readonly class TokenVerifyProcessor implements ProcessorInterface
{
    public function __construct(private TokenVerifier $verifier)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TokenVerifyOutput
    {
        if (!$data instanceof TokenVerifyInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $verified = $this->verifier->verify($data->token);

        $output = new TokenVerifyOutput();
        $output->valid = $verified->valid;
        $output->username = $verified->username;
        $output->expiresAt = $verified->expiresAt?->format(\DateTimeInterface::ATOM);

        return $output;
    }
}
