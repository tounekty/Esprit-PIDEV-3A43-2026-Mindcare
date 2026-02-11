<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[]
     */
    public function findBySearch(?string $query): array
    {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.dateEvent', 'ASC');

        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.titre)', ':q'),
                    $qb->expr()->like('LOWER(e.description)', ':q'),
                    $qb->expr()->like('LOWER(e.lieu)', ':q')
                )
            )
            ->setParameter('q', '%' . strtolower($query) . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
