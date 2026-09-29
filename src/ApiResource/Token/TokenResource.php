<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Token;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\State\Token\TokenCreateProcessor;
use Phprise\KoenmaID\State\Token\TokenRefreshProcessor;
use Phprise\KoenmaID\State\Token\TokenRevokeProcessor;
use Phprise\KoenmaID\State\Token\TokenVerifyProcessor;

#[ApiResource(
    shortName: 'Token',
    description: 'Token lifecycle operations. Every operation uses POST because each one carries a secret in the request body, but they serve different purposes: create, refresh, verify and revoke.',
    operations: [
        new Post(
            uriTemplate: '/token/create',
            openapi: new OpenApiOperation(summary: 'Create an access token', description: 'Authenticates a user with an API Key, username and password, then issues a new access token and a new refresh token. Use this operation to start a session.'),
            input: TokenCreateInput::class,
            output: TokenOutput::class,
            processor: TokenCreateProcessor::class,
            denormalizationContext: ['groups' => ['token:create']],
            validationContext: ['groups' => ['token:create']],
            normalizationContext: ['groups' => ['token:get']],
        ),
        new Post(
            uriTemplate: '/token/refresh',
            openapi: new OpenApiOperation(summary: 'Refresh an access token', description: 'Exchanges a valid refresh token for a new access token and a new refresh token. Use this operation when the previous access token has expired.'),
            input: TokenRefreshInput::class,
            output: TokenOutput::class,
            processor: TokenRefreshProcessor::class,
            denormalizationContext: ['groups' => ['token:refresh']],
            validationContext: ['groups' => ['token:refresh']],
            normalizationContext: ['groups' => ['token:get']],
        ),
        new Post(
            uriTemplate: '/token/verify',
            openapi: new OpenApiOperation(summary: 'Verify an access token', description: 'Checks whether an access token is valid and returns information about it, such as the username and the expiration date. Use this operation to inspect a token without consuming it.'),
            input: TokenVerifyInput::class,
            output: TokenVerifyOutput::class,
            processor: TokenVerifyProcessor::class,
            denormalizationContext: ['groups' => ['token:verify']],
            validationContext: ['groups' => ['token:verify']],
            normalizationContext: ['groups' => ['token:get']],
        ),
        new Post(
            uriTemplate: '/token/revoke',
            openapi: new OpenApiOperation(summary: 'Revoke a refresh token', description: 'Invalidates a refresh token before its natural expiration. Use this operation to end a session or to respond to a suspected leak.'),
            input: TokenRevokeInput::class,
            output: TokenRevokeOutput::class,
            processor: TokenRevokeProcessor::class,
            denormalizationContext: ['groups' => ['token:revoke']],
            validationContext: ['groups' => ['token:revoke']],
            normalizationContext: ['groups' => ['token:get']],
        ),
    ],
)]
final class TokenResource
{
}
