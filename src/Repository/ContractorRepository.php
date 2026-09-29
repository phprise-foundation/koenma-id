<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Repository;

use Phprise\KoenmaID\Entity\Contractor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contractor>
 */
class ContractorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contractor::class);
    }
}
