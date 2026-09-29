<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Partner;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class PartnerPatchInput
{
    /** New legal name of the partner. */
    #[Groups(['partner:patch'])]
    #[Assert\Length(max: 255, groups: ['partner:patch'])]
    public ?string $name = null;

    /** New contact email address of the partner. */
    #[Groups(['partner:patch'])]
    #[Assert\Email(groups: ['partner:patch'])]
    #[Assert\Length(max: 255, groups: ['partner:patch'])]
    public ?string $emailAddress = null;
}
