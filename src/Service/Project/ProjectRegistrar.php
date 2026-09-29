<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Project;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectRegistrar
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function register(Partner $partner, string $name, ?string $description): Project
    {
        $project = new Project($partner, $name);
        $project->describe($description);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }
}
