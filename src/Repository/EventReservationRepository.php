<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventReservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventReservation>
 */
class EventReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventReservation::class);
    }

    public function countActiveByEvent(Event $event): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :event')
            ->andWhere('r.statut IN (:statuses)')
            ->setParameter('event', $event)
            ->setParameter('statuses', [
                EventReservation::STATUS_PENDING,
                EventReservation::STATUS_ACCEPTED,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

<<<<<<< HEAD
=======
    public function findByConfirmationToken(string $token): ?EventReservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.confirmationToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

>>>>>>> origin/sara
    public function findLatestByUserAndEvent(User $user, Event $event): ?EventReservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.event = :event')
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->orderBy('r.dateReservation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return EventReservation[]
     */
    public function findByFilters(?string $status, ?string $search, string $sortBy = 'dateReservation', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.event', 'e')
            ->addSelect('u', 'e');

        if ($status && $status !== 'all') {
            $qb->andWhere('r.statut = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.firstName)', ':search'),
                    $qb->expr()->like('LOWER(u.lastName)', ':search'),
                    $qb->expr()->like('LOWER(CONCAT(u.firstName, \' \', u.lastName))', ':search'),
                    $qb->expr()->like('LOWER(e.titre)', ':search'),
                    $qb->expr()->like('LOWER(r.nom)', ':search'),
                    $qb->expr()->like('LOWER(r.prenom)', ':search'),
                    $qb->expr()->like('LOWER(r.telephone)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $validSortFields = ['dateReservation', 'statut'];
        $validSortOrders = ['ASC', 'DESC'];

        if (!in_array($sortBy, $validSortFields, true)) {
            $sortBy = 'dateReservation';
        }

        if (!in_array(strtoupper($sortOrder), $validSortOrders, true)) {
            $sortOrder = 'DESC';
        }

        $qb->orderBy('r.' . $sortBy, strtoupper($sortOrder));

        return $qb->getQuery()->getResult();
    }
<<<<<<< HEAD
=======

    /**
     * Get event dates reserved by a user (accepted or pending)
     * @return string[] Array of date strings 'Y-m-d'
     */
    public function findReservedEventDatesByUser(User $user): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('DISTINCT e.dateEvent')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->andWhere('r.statut IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [
                EventReservation::STATUS_PENDING,
                EventReservation::STATUS_ACCEPTED,
            ])
            ->getQuery()
            ->getScalarResult();

        return array_map(fn($row) => (new \DateTime($row['dateEvent']))->format('Y-m-d'), $results);
    }

    /**
     * Find accepted reservations where the event is within the next N hours and SMS not yet sent
     * @return EventReservation[]
     */
    public function findUpcomingForSmsReminder(int $hoursAhead = 24): array
    {
        $now = new \DateTime();
        $deadline = (clone $now)->modify("+{$hoursAhead} hours");

        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.statut = :status')
            ->andWhere('e.dateEvent > :now')
            ->andWhere('e.dateEvent <= :deadline')
            ->andWhere('r.smsReminderSent = false')
            ->setParameter('status', EventReservation::STATUS_ACCEPTED)
            ->setParameter('now', $now)
            ->setParameter('deadline', $deadline)
            ->getQuery()
            ->getResult();
    }
>>>>>>> origin/sara
}
