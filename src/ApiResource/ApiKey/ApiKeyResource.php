<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\ApiKey;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\State\ApiKey\ApiKeyCollectionProvider;
use Phprise\KoenmaID\State\ApiKey\ApiKeyItemProvider;
use Phprise\KoenmaID\State\ApiKey\ApiKeyPatchProcessor;
use Phprise\KoenmaID\State\ApiKey\ApiKeyPostProcessor;

#[ApiResource(
    description: 'An API Key authenticates a project in machine-to-machine calls. The plain key is shown only once, in the response of the create operation, and is never stored.',
    shortName: 'ApiKey',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List API Keys of a project', description: 'Returns every API Key that belongs to the given project. The plain key is never returned.'),
            uriTemplate: '/projects/{projectId}/api-keys',
            provider: ApiKeyCollectionProvider::class,
            normalizationContext: ['groups' => ['api_key:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get an API Key', description: 'Returns a single API Key by its identifier. The plain key is never returned.'),
            uriTemplate: '/api-keys/{id}',
            provider: ApiKeyItemProvider::class,
            normalizationContext: ['groups' => ['api_key:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create an API Key', description: 'Issues a new API Key for the given project. The plain key is returned only in this response and cannot be recovered later.'),
            uriTemplate: '/projects/{projectId}/api-keys',
            input: ApiKeyInput::class,
            processor: ApiKeyPostProcessor::class,
            denormalizationContext: ['groups' => ['api_key:post']],
            validationContext: ['groups' => ['api_key:post']],
            normalizationContext: ['groups' => ['api_key:get', 'api_key:post']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update an API Key', description: 'Updates the name of an API Key.'),
            uriTemplate: '/api-keys/{id}',
            input: ApiKeyPatchInput::class,
            provider: ApiKeyItemProvider::class,
            processor: ApiKeyPatchProcessor::class,
            denormalizationContext: ['groups' => ['api_key:patch']],
            validationContext: ['groups' => ['api_key:patch']],
            normalizationContext: ['groups' => ['api_key:get']],
        ),
    ],
)]
final class ApiKeyResource
{
}
