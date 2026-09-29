<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Doctrine\Type;

use Phprise\KoenmaID\ValueObject\RefreshTokenId;

final class RefreshTokenIdType extends AbstractPrefixedIdType
{
    public const string NAME = 'refresh_token_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return RefreshTokenId::class;
    }
}
