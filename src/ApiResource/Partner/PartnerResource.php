<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Partner;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\State\Partner\PartnerCollectionProvider;
use Phprise\KoenmaID\State\Partner\PartnerItemProvider;
use Phprise\KoenmaID\State\Partner\PartnerPatchProcessor;
use Phprise\KoenmaID\State\Partner\PartnerPostProcessor;

#[ApiResource(
    description: 'A partner is the organization that contracts the API. It owns projects and contractors.',
    shortName: 'Partner',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List partners', description: 'Returns every partner.'),
            uriTemplate: '/partners',
            provider: PartnerCollectionProvider::class,
            normalizationContext: ['groups' => ['partner:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get a partner', description: 'Returns a single partner by its identifier.'),
            uriTemplate: '/partners/{id}',
            provider: PartnerItemProvider::class,
            normalizationContext: ['groups' => ['partner:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create a partner', description: 'Registers a new partner.'),
            uriTemplate: '/partners',
            input: PartnerInput::class,
            processor: PartnerPostProcessor::class,
            denormalizationContext: ['groups' => ['partner:post']],
            validationContext: ['groups' => ['partner:post']],
            normalizationContext: ['groups' => ['partner:get']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update a partner', description: 'Updates the name or the email address of a partner.'),
            uriTemplate: '/partners/{id}',
            input: PartnerPatchInput::class,
            provider: PartnerItemProvider::class,
            processor: PartnerPatchProcessor::class,
            denormalizationContext: ['groups' => ['partner:patch']],
            validationContext: ['groups' => ['partner:patch']],
            normalizationContext: ['groups' => ['partner:get']],
        ),
    ],
)]
final class PartnerResource
{
}
