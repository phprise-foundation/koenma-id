<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\User;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\State\User\UserCollectionProvider;
use Phprise\KoenmaID\State\User\UserItemProvider;
use Phprise\KoenmaID\State\User\UserPatchProcessor;
use Phprise\KoenmaID\State\User\UserPostProcessor;

#[ApiResource(
    description: 'A user is a person that authenticates with username and password. It belongs to a contractor and is created with an API Key.',
    shortName: 'User',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List users of a contractor', description: 'Returns every user that belongs to the given contractor.'),
            uriTemplate: '/contractors/{contractorId}/users',
            provider: UserCollectionProvider::class,
            normalizationContext: ['groups' => ['user:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get a user', description: 'Returns a single user by its identifier.'),
            uriTemplate: '/users/{id}',
            provider: UserItemProvider::class,
            normalizationContext: ['groups' => ['user:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create a user', description: 'Registers a new user under the given contractor. The request must carry a valid security key of the partner that owns the contractor.'),
            uriTemplate: '/contractors/{contractorId}/users',
            input: UserInput::class,
            processor: UserPostProcessor::class,
            denormalizationContext: ['groups' => ['user:post']],
            validationContext: ['groups' => ['user:post']],
            normalizationContext: ['groups' => ['user:get']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update a user', description: 'Updates the username, the email address or the password of a user.'),
            uriTemplate: '/users/{id}',
            input: UserPatchInput::class,
            provider: UserItemProvider::class,
            processor: UserPatchProcessor::class,
            denormalizationContext: ['groups' => ['user:patch']],
            validationContext: ['groups' => ['user:patch']],
            normalizationContext: ['groups' => ['user:get']],
        ),
    ],
)]
final class UserResource
{
}
