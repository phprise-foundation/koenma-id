<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ValueObject;

final class RefreshTokenId extends AbstractPrefixedId
{
    protected static function prefix(): string
    {
        return 'rtk_';
    }
}
