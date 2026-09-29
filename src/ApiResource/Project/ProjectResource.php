<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Project;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\State\Project\ProjectCollectionProvider;
use Phprise\KoenmaID\State\Project\ProjectItemProvider;
use Phprise\KoenmaID\State\Project\ProjectPatchProcessor;
use Phprise\KoenmaID\State\Project\ProjectPostProcessor;

#[ApiResource(
    description: 'A project belongs to a partner and groups the API Keys used to authenticate machine-to-machine calls.',
    shortName: 'Project',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List projects of a partner', description: 'Returns every project that belongs to the given partner.'),
            uriTemplate: '/partners/{partnerId}/projects',
            provider: ProjectCollectionProvider::class,
            normalizationContext: ['groups' => ['project:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get a project', description: 'Returns a single project by its identifier.'),
            uriTemplate: '/projects/{id}',
            provider: ProjectItemProvider::class,
            normalizationContext: ['groups' => ['project:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create a project', description: 'Registers a new project under the given partner.'),
            uriTemplate: '/partners/{partnerId}/projects',
            input: ProjectInput::class,
            processor: ProjectPostProcessor::class,
            denormalizationContext: ['groups' => ['project:post']],
            validationContext: ['groups' => ['project:post']],
            normalizationContext: ['groups' => ['project:get']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update a project', description: 'Updates the name or the description of a project.'),
            uriTemplate: '/projects/{id}',
            input: ProjectPatchInput::class,
            provider: ProjectItemProvider::class,
            processor: ProjectPatchProcessor::class,
            denormalizationContext: ['groups' => ['project:patch']],
            validationContext: ['groups' => ['project:patch']],
            normalizationContext: ['groups' => ['project:get']],
        ),
    ],
)]
final class ProjectResource
{
}
