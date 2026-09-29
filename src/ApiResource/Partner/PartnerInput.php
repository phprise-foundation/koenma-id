<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Partner;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class PartnerInput
{
    /** Legal name of the partner. */
    #[Groups(['partner:post'])]
    #[Assert\NotBlank(groups: ['partner:post'])]
    #[Assert\Length(max: 255, groups: ['partner:post'])]
    public string $name = '';

    /** Contact email address of the partner. */
    #[Groups(['partner:post'])]
    #[Assert\NotBlank(groups: ['partner:post'])]
    #[Assert\Email(groups: ['partner:post'])]
    #[Assert\Length(max: 255, groups: ['partner:post'])]
    public string $emailAddress = '';

    /** National registration document of the partner. */
    #[Groups(['partner:post'])]
    #[Assert\NotBlank(groups: ['partner:post'])]
    #[Assert\Length(max: 32, groups: ['partner:post'])]
    public string $document = '';
}
