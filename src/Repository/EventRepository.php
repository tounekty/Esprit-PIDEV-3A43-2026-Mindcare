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
    public function findBySearch(?string $nom = null, ?string $lieu = null, string $tri = 'titre'): array
    {
        $qb = $this->createQueryBuilder('e');

        // map requested sort to entity field
        $sortField = match ($tri) {
            'date', 'dateEvent', 'dateHeure' => 'e.dateHeure',
            'lieu' => 'e.lieu',
            'capacite', 'capaciteMax' => 'e.capaciteMax',
            default => 'e.titre',
        };

        $qb->orderBy($sortField, 'ASC');

        if ($nom) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.titre)', ':q'),
                    $qb->expr()->like('LOWER(e.description)', ':q')
                )
            )
            ->setParameter('q', '%' . strtolower($nom) . '%');
        }

        if ($lieu) {
            $qb->andWhere($qb->expr()->like('LOWER(e.lieu)', ':lieu'))
               ->setParameter('lieu', '%' . strtolower($lieu) . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
