<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Doctrine\Type;

use Phprise\KoenmaID\ValueObject\PartnerId;

final class PartnerIdType extends AbstractPrefixedIdType
{
    public const string NAME = 'partner_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return PartnerId::class;
    }
}
