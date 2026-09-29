<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ValueObject;

final class PartnerId extends AbstractPrefixedId
{
    protected static function prefix(): string
    {
        return 'prt_';
    }
}
