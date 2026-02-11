<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

<<<<<<< HEAD
=======
/**
 * @extends ServiceEntityRepository<Event>
 */
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

<<<<<<< HEAD
    public function findBySearch(?string $nom, ?string $lieu, string $tri): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($nom) {
            $qb->andWhere('e.titre LIKE :nom')->setParameter('nom', '%'.$nom.'%');
        }
        if ($lieu) {
            $qb->andWhere('e.lieu LIKE :lieu')->setParameter('lieu', '%'.$lieu.'%');
        }

        $qb->orderBy('e.' . $tri, 'ASC');

        return $qb->getQuery()->getResult();
    }
}
=======
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
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
