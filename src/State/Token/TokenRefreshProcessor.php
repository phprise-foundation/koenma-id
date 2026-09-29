<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\State\Token;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Phprise\KoenmaID\ApiResource\Token\TokenOutput;
use Phprise\KoenmaID\ApiResource\Token\TokenRefreshInput;
use Phprise\KoenmaID\Service\Token\IssuedToken;
use Phprise\KoenmaID\Service\Token\TokenRefresher;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<TokenRefreshInput, TokenOutput>
 */
final readonly class TokenRefreshProcessor implements ProcessorInterface
{
    public function __construct(private TokenRefresher $refresher)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TokenOutput
    {
        if (!$data instanceof TokenRefreshInput) {
            throw new BadRequestHttpException('Invalid payload.');
        }

        return $this->toOutput($this->refresher->refresh($data->refreshToken));
    }

    private function toOutput(IssuedToken $issued): TokenOutput
    {
        $output = new TokenOutput();
        $output->accessToken = $issued->accessToken;
        $output->refreshToken = $issued->refreshToken;

        return $output;
    }
}
