<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Doctrine\Type;

use Phprise\KoenmaID\ValueObject\ProjectId;

final class ProjectIdType extends AbstractPrefixedIdType
{
    public const string NAME = 'project_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ProjectId::class;
    }
}
