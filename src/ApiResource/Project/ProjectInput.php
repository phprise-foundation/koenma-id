<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Project;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ProjectInput
{
    /** Name of the project. */
    #[Groups(['project:post'])]
    #[Assert\NotBlank(groups: ['project:post'])]
    #[Assert\Length(max: 255, groups: ['project:post'])]
    public string $name = '';

    /** Optional description of the project. */
    #[Groups(['project:post'])]
    #[Assert\Length(max: 1000, groups: ['project:post'])]
    public ?string $description = null;
}
