<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ValueObject;

final class ContractorId extends AbstractPrefixedId
{
    protected static function prefix(): string
    {
        return 'cnt_';
    }
}
