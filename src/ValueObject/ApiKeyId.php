<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ValueObject;

final class ApiKeyId extends AbstractPrefixedId
{
    protected static function prefix(): string
    {
        return 'aky_';
    }
}
