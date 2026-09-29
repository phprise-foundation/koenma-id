<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Repository;

use Phprise\KoenmaID\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiKey>
 */
class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function findOneByHash(string $keyHash): ?ApiKey
    {
        return $this->findOneBy(['keyHash' => $keyHash]);
    }
}
