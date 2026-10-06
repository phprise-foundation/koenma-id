<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Entity;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use PHPUnit\Framework\TestCase;

final class ProjectUnitTest extends TestCase
{
    public function testInstantiatesWithoutPartnerAndSetPartnerIsFluent(): void
    {
        $project = new Project();

        self::assertInstanceOf(Project::class, $project);

        $partner = new Partner('Test Partner', 'test@example.com', '1234567890');

        self::assertSame($project, $project->setPartner($partner));
        self::assertSame($partner, $project->getPartner());
    }

    public function testSetNameAndDescriptionAreFluent(): void
    {
        $project = new Project();

        self::assertSame($project, $project->setName('Project Alpha'));
        self::assertSame($project, $project->setDescription('First project'));
        self::assertSame('Project Alpha', $project->getName());
        self::assertSame('First project', $project->getDescription());
    }
}
