<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Doctrine\IdGenerator;

use Phprise\KoenmaID\Doctrine\Type\AbstractPrefixedIdType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;

final class PrefixedIdGenerator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, ?object $entity): mixed
    {
        $metadata = $em->getClassMetadata($entity::class);
        $typeName = $metadata->getTypeOfField($metadata->identifier[0]);
        $type = Type::getType($typeName);

        if (!$type instanceof AbstractPrefixedIdType) {
            throw new \LogicException(\sprintf('Identifier type "%s" must extend %s.', $typeName, AbstractPrefixedIdType::class));
        }

        return $type->generateValueObject();
    }
}
