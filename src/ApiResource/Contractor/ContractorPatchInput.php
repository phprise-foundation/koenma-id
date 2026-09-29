<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Contractor;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ContractorPatchInput
{
    /** New legal name of the contractor. */
    #[Groups(['contractor:patch'])]
    #[Assert\Length(max: 255, groups: ['contractor:patch'])]
    public ?string $name = null;
}
