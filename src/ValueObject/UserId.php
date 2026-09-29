<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ValueObject;

final class UserId extends AbstractPrefixedId
{
    protected static function prefix(): string
    {
        return 'usr_';
    }
}
