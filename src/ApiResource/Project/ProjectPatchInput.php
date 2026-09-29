<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Project;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ProjectPatchInput
{
    /** New name of the project. */
    #[Groups(['project:patch'])]
    #[Assert\Length(max: 255, groups: ['project:patch'])]
    public ?string $name = null;

    /** New description of the project. */
    #[Groups(['project:patch'])]
    #[Assert\Length(max: 1000, groups: ['project:patch'])]
    public ?string $description = null;
}
