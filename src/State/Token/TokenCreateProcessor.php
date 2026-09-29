<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Token;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Token\TokenCreateInput;
use Phprise\KoenmaID\ApiResource\Token\TokenOutput;
use Phprise\KoenmaID\Service\Token\IssuedToken;
use Phprise\KoenmaID\Service\Token\TokenAuthenticator;
use Phprise\KoenmaID\Service\Token\TokenIssuer;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<TokenCreateInput, TokenOutput>
 */
final readonly class TokenCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private TokenAuthenticator $authenticator,
        private TokenIssuer $issuer,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TokenOutput
    {
        if (!$data instanceof TokenCreateInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        $user = $this->authenticator->authenticate($data->apiKey, $data->username, $data->password);

        return $this->toOutput($this->issuer->issue($user));
    }

    private function toOutput(IssuedToken $issued): TokenOutput
    {
        $output = new TokenOutput();
        $output->accessToken = $issued->accessToken;
        $output->refreshToken = $issued->refreshToken;

        return $output;
    }
}
