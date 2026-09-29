<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Project;

use Phprise\KoenmaID\Entity\Project;
use Symfony\Component\Serializer\Attribute\Groups;

final class ProjectOutput
{
    /** Unique identifier of the project. */
    #[Groups(['project:get'])]
    public string $id = '';

    /** Identifier of the partner that owns the project. */
    #[Groups(['project:get'])]
    public string $partnerId = '';

    /** Name of the project. */
    #[Groups(['project:get'])]
    public string $name = '';

    /** Optional description of the project. */
    #[Groups(['project:get'])]
    public ?string $description = null;

    /** Whether the project is active. */
    #[Groups(['project:get'])]
    public bool $active = true;

    /** Creation date in ISO 8601 format. */
    #[Groups(['project:get'])]
    public string $createdAt = '';

    public static function fromEntity(Project $project): self
    {
        $output = new self();
        $output->id = (string) $project->id();
        $output->partnerId = (string) $project->partner()->id();
        $output->name = $project->name();
        $output->description = $project->description();
        $output->active = $project->active();
        $output->createdAt = $project->createdAt()->format(\DateTimeInterface::ATOM);

        return $output;
    }
}
