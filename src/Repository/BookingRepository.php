<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findBySearch(?string $nom, ?string $lieu, string $tri): array
    {
        $qb = $this->createQueryBuilder('b')
            ->join('b.event', 'e'); // Jointure avec l'entité Event

        if ($nom) {
            $qb->andWhere('b.nomEtudiant LIKE :nom')
               ->setParameter('nom', '%'.$nom.'%');
        }

        if ($lieu) {
            $qb->andWhere('e.lieu LIKE :lieu')
               ->setParameter('lieu', '%'.$lieu.'%');
        }

        // Gestion du tri dynamique
        if ($tri === 'lieu') {
            $qb->orderBy('e.lieu', 'ASC');
        } elseif ($tri === 'dateReservation') {
            $qb->orderBy('b.dateReservation', 'DESC');
        } else {
            $qb->orderBy('b.nomEtudiant', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }
}