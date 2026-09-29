<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Doctrine\Type;

use Phprise\KoenmaID\ValueObject\ContractorId;

final class ContractorIdType extends AbstractPrefixedIdType
{
    public const string NAME = 'contractor_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ContractorId::class;
    }
}
