<?php

namespace App\Repository;

use App\Entity\SujetForum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SujetForum>
 */
class SujetForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SujetForum::class);
    }

    public function findBySearch(?string $query, ?string $status): array
    {
        $qb = $this->createQueryBuilder('s');

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(s.titre) LIKE :query OR LOWER(s.description) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->orderBy('s.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
