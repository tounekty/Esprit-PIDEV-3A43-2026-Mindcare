<?php

namespace App\Repository;

use App\Entity\SujetForum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
<<<<<<< HEAD
use Doctrine\ORM\QueryBuilder;
=======
>>>>>>> origin/sara
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
<<<<<<< HEAD
        return $this->createSearchQueryBuilder($query, $status)
            ->getQuery()
            ->getResult();
    }

    public function createSearchQueryBuilder(?string $query, ?string $status): QueryBuilder
    {
        return $this->createFilteredQueryBuilder($query, $status, 'date', 'DESC');
    }

    public function createFilteredQueryBuilder(?string $query, ?string $status, string $sort, string $direction): QueryBuilder
    {
=======
>>>>>>> origin/sara
        $qb = $this->createQueryBuilder('s');

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(s.titre) LIKE :query OR LOWER(s.description) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

<<<<<<< HEAD
        if ($status !== null && $status !== '' && $status !== 'all') {
=======
        if ($status !== null && $status !== '') {
>>>>>>> origin/sara
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

<<<<<<< HEAD
        $sortMap = [
            'date' => 's.dateCreation',
            'title' => 's.titre',
            'status' => 's.status',
        ];

        $sortField = $sortMap[$sort] ?? $sortMap['date'];
        $sortDirection = in_array(strtoupper($direction), ['ASC', 'DESC'], true) ? strtoupper($direction) : 'DESC';

        return $qb
            ->orderBy('s.isPinned', 'DESC')
            ->addOrderBy($sortField, $sortDirection)
            ->addOrderBy('s.id', 'DESC');
=======
        return $qb->orderBy('s.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
>>>>>>> origin/sara
    }
}
