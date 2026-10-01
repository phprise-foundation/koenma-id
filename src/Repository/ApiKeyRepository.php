<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Repository;

use Phprise\KoenmaID\Doctrine\Type\ProjectIdType;
use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Project;
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

    /**
     * @return list<ApiKey>
     */
    public function findActiveByProject(Project $project): array
    {
        $queryBuilder = $this->createQueryBuilder('apiKey');
        $queryBuilder
            ->andWhere('apiKey.project = :project')
            ->andWhere('apiKey.deletedAt IS NULL')
            ->andWhere('apiKey.expiresAt IS NULL OR apiKey.expiresAt > :now')
            ->setParameter('project', $project->id(), ProjectIdType::NAME)
            ->setParameter('now', new \DateTimeImmutable());

        return $queryBuilder->getQuery()->getResult();
    }
}
