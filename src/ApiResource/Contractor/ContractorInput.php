<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Contractor;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ContractorInput
{
    /** Legal name of the contractor. */
    #[Groups(['contractor:post'])]
    #[Assert\NotBlank(groups: ['contractor:post'])]
    #[Assert\Length(max: 255, groups: ['contractor:post'])]
    public string $name = '';

    /** National registration document of the contractor. */
    #[Groups(['contractor:post'])]
    #[Assert\NotBlank(groups: ['contractor:post'])]
    #[Assert\Length(max: 32, groups: ['contractor:post'])]
    public string $document = '';
}
