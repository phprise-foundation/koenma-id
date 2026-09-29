<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ValueObject;

final class ProjectId extends AbstractPrefixedId
{
    protected static function prefix(): string
    {
        return 'prj_';
    }
}
