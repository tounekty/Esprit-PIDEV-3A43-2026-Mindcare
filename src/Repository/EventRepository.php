<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

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