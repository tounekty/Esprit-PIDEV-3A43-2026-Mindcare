<?php

namespace App\Repository;

use App\Entity\UserStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserStats>
 */
class UserStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserStats::class);
    }

    public function findByUser($user): ?UserStats
    {
        return $this->findOneBy(['user' => $user]);
    }
}
