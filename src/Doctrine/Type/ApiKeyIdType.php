<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Doctrine\Type;

use Phprise\KoenmaID\ValueObject\ApiKeyId;

final class ApiKeyIdType extends AbstractPrefixedIdType
{
    public const string NAME = 'api_key_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ApiKeyId::class;
    }
}
