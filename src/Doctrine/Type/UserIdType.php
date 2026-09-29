<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Doctrine\Type;

use Phprise\KoenmaID\ValueObject\UserId;

final class UserIdType extends AbstractPrefixedIdType
{
    public const string NAME = 'user_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return UserId::class;
    }
}
