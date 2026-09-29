<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Contractor;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\State\Contractor\ContractorCollectionProvider;
use Phprise\KoenmaID\State\Contractor\ContractorItemProvider;
use Phprise\KoenmaID\State\Contractor\ContractorPatchProcessor;
use Phprise\KoenmaID\State\Contractor\ContractorPostProcessor;

#[ApiResource(
    description: 'A contractor belongs to a partner and groups the users that authenticate with username and password.',
    shortName: 'Contractor',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List contractors of a partner', description: 'Returns every contractor that belongs to the given partner.'),
            uriTemplate: '/partners/{partnerId}/contractors',
            provider: ContractorCollectionProvider::class,
            normalizationContext: ['groups' => ['contractor:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get a contractor', description: 'Returns a single contractor by its identifier.'),
            uriTemplate: '/contractors/{id}',
            provider: ContractorItemProvider::class,
            normalizationContext: ['groups' => ['contractor:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create a contractor', description: 'Registers a new contractor under the given partner.'),
            uriTemplate: '/partners/{partnerId}/contractors',
            input: ContractorInput::class,
            processor: ContractorPostProcessor::class,
            denormalizationContext: ['groups' => ['contractor:post']],
            validationContext: ['groups' => ['contractor:post']],
            normalizationContext: ['groups' => ['contractor:get']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update a contractor', description: 'Updates the name of a contractor.'),
            uriTemplate: '/contractors/{id}',
            input: ContractorPatchInput::class,
            provider: ContractorItemProvider::class,
            processor: ContractorPatchProcessor::class,
            denormalizationContext: ['groups' => ['contractor:patch']],
            validationContext: ['groups' => ['contractor:patch']],
            normalizationContext: ['groups' => ['contractor:get']],
        ),
    ],
)]
final class ContractorResource
{
}
