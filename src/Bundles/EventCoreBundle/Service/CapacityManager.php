<?php

namespace App\Bundles\EventCoreBundle\Service;

use App\Entity\Event;
use App\Entity\EventReservation;
use App\Repository\EventReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion intelligente des capacités et liste d'attente
 * 
 * Responsabilités:
 * - Suivi dynamique de la capacité
 * - Gestion automatique du statut (COMPLET/DISPONIBLE)
 * - Gestion de la liste d'attente
 * - Auto-promotion depuis la liste d'attente
 */
class CapacityManager
{
    public const EVENT_STATUS_AVAILABLE = 'available';
    public const EVENT_STATUS_FULL = 'full';
    public const EVENT_STATUS_WAITING_AVAILABLE = 'waiting_available';

    public function __construct(
        private EventReservationRepository $reservationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupère les stats de capacité d'un événement
     */
    public function getCapacityStats(Event $event): array
    {
        $total = $this->reservationRepository->countActiveByEvent($event);
        $waitingCount = $this->reservationRepository->countWaitingByEvent($event);
        $capacity = $event->getCapacite();
        $available = max(0, $capacity - $total);
        $isFull = $total >= $capacity;
        $hasWaiting = $waitingCount > 0;

        return [
            'capacity' => $capacity,
            'confirmed' => $total,
            'waiting' => $waitingCount,
            'available' => $available,
            'isFull' => $isFull,
            'status' => $this->getEventStatus($isFull, $hasWaiting),
            'percentUsed' => $capacity > 0 ? ($total / $capacity * 100) : 0,
        ];
    }

    /**
     * Détermine le statut de l'événement
     */
    private function getEventStatus(bool $isFull, bool $hasWaiting): string
    {
        if ($hasWaiting && !$isFull) {
            return self::EVENT_STATUS_WAITING_AVAILABLE;
        }
        return $isFull ? self::EVENT_STATUS_FULL : self::EVENT_STATUS_AVAILABLE;
    }

    /**
     * Crée une réservation ou ajoute à la liste d'attente automatiquement
     * 
     * Retourne:
     * - ['status' => 'confirmed', 'reservation' => EventReservation] si confirmé
     * - ['status' => 'waiting', 'reservation' => EventReservation, 'waitingPosition' => int] si en attente
     * - ['status' => 'error', 'message' => string] si erreur
     */
    public function createReservationOrWaiting(
        Event $event,
        mixed $user,
        string $nom = '',
        string $prenom = '',
        string $telephone = '',
        string $commentaire = ''
    ): array
    {
        // Vérifier si l'événement est déjà complet
        $activeCount = $this->reservationRepository->countActiveByEvent($event);
        $capacity = $event->getCapacite();

        $reservation = new EventReservation();
        $reservation->setEvent($event);
        $reservation->setUser($user);
        $reservation->setDateReservation(new \DateTime());
        $reservation->setNom($nom);
        $reservation->setPrenom($prenom);
        $reservation->setTelephone($telephone);
        
        if ($commentaire) {
            $reservation->setCommentaire($commentaire);
        }

        // Si place disponible > Confirmation immédiate
        if ($activeCount < $capacity) {
            $reservation->setStatut(EventReservation::STATUS_ACCEPTED);
            $reservation->setIsWaitingList(false);
            $reservation->setWaitingPosition(null);

            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            return [
                'status' => 'confirmed',
                'isWaitingList' => false,
                'reservation' => $reservation,
                'message' => 'Réservation confirmée immédiatement !',
            ];
        }

        // Sinon > Liste d'attente
        $nextPosition = $this->getNextWaitingPosition($event);
        $reservation->setStatut(EventReservation::STATUS_PENDING);
        $reservation->setIsWaitingList(true);
        $reservation->setWaitingPosition($nextPosition);

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return [
            'status' => 'waiting',
            'isWaitingList' => true,
            'reservation' => $reservation,
            'waitingPosition' => $nextPosition,
            'message' => "Événement complet. Vous avez été ajouté à la position {$nextPosition} de la liste d'attente.",
        ];
    }

    /**
     * Obtient la prochaine position dans la liste d'attente
     */
    private function getNextWaitingPosition(Event $event): int
    {
        $maxPosition = $this->reservationRepository->createQueryBuilder('r')
            ->select('MAX(r.waitingPosition)')
            ->where('r.event = :event')
            ->andWhere('r.isWaitingList = true')
            ->setParameter('event', $event)
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxPosition ?? 0) + 1;
    }

    /**
     * Annule une réservation et promeut automatiquement le premier en attente
     */
    public function cancelReservationAndPromote(EventReservation $reservation): array
    {
        $event = $reservation->getEvent();
        $reservation->setStatut(EventReservation::STATUS_CANCELLED);

        // Si c'était une réservation confirmée (pas d'attente)
        if (!$reservation->isWaitingList()) {
            // Essayer de promouvoir le premier en attente
            $nextWaiting = $this->getFirstWaitingReservation($event);

            if ($nextWaiting) {
                $nextWaiting->setIsWaitingList(false);
                $nextWaiting->setWaitingPosition(null);
                // Réorganiser les positions de la liste d'attente
                $this->reorganizeWaitingPositions($event);

                $this->entityManager->flush();

                return [
                    'status' => 'promoted',
                    'cancelled' => $reservation,
                    'promoted' => $nextWaiting,
                    'message' => 'Une personne en attente a été promue.',
                ];
            }
        } else {
            // C'était une attente > juste réorganiser
            $this->reorganizeWaitingPositions($event);
        }

        $this->entityManager->flush();

        return [
            'status' => 'cancelled',
            'cancelled' => $reservation,
            'message' => 'Réservation annulée.',
        ];
    }

    /**
     * Obtient le premier en liste d'attente pour un événement
     */
    private function getFirstWaitingReservation(Event $event): ?EventReservation
    {
        return $this->reservationRepository->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.isWaitingList = true')
            ->andWhere('r.statut = :status')
            ->setParameter('event', $event)
            ->setParameter('status', EventReservation::STATUS_PENDING)
            ->orderBy('r.waitingPosition', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Réorganise la numérotation de la liste d'attente
     */
    private function reorganizeWaitingPositions(Event $event): void
    {
        $waitingReservations = $this->reservationRepository->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.isWaitingList = true')
            ->setParameter('event', $event)
            ->orderBy('r.waitingPosition', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($waitingReservations as $index => $reservation) {
            $reservation->setWaitingPosition($index + 1);
        }
    }

    /**
     * Accepte une réservation (changement de statut)
     */
    public function acceptReservation(EventReservation $reservation): bool
    {
        if ($reservation->getStatut() !== EventReservation::STATUS_PENDING) {
            return false;
        }

        $reservation->setStatut(EventReservation::STATUS_ACCEPTED);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Refuse une réservation
     */
    public function refuseReservation(EventReservation $reservation): bool
    {
        if (!in_array($reservation->getStatut(), [
            EventReservation::STATUS_PENDING,
            EventReservation::STATUS_ACCEPTED,
        ])) {
            return false;
        }

        $wasWaiting = $reservation->isWaitingList();
        $reservation->setStatut(EventReservation::STATUS_REFUSED);
        $this->entityManager->flush();

        // Si c'était une réservation confirmée > prendre le premier en attente
        if (!$wasWaiting) {
            $event = $reservation->getEvent();
            $nextWaiting = $this->getFirstWaitingReservation($event);

            if ($nextWaiting) {
                $nextWaiting->setIsWaitingList(false);
                $nextWaiting->setWaitingPosition(null);
                $this->reorganizeWaitingPositions($event);
                $this->entityManager->flush();

                return true;
            }
        }

        if ($wasWaiting) {
            $this->reorganizeWaitingPositions($reservation->getEvent());
            $this->entityManager->flush();
        }

        return true;
    }

    /**
     * Promeut manuellement une réservation de la liste d'attente
     */
    public function promoteFromWaiting(EventReservation $reservation): bool
    {
        if (!$reservation->isWaitingList()) {
            return false;
        }

        $event = $reservation->getEvent();
        $activeCount = $this->reservationRepository->countActiveByEvent($event);

        if ($activeCount >= $event->getCapacite()) {
            return false; // Pas de place disponible
        }

        $reservation->setIsWaitingList(false);
        $reservation->setWaitingPosition(null);
        $this->reorganizeWaitingPositions($event);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Récupère les réservations confirmées (non en attente)
     */
    public function getConfirmedReservations(Event $event): array
    {
        return $this->reservationRepository->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.isWaitingList = false')
            ->andWhere('r.statut IN (:statuses)')
            ->setParameter('event', $event)
            ->setParameter('statuses', [
                EventReservation::STATUS_PENDING,
                EventReservation::STATUS_ACCEPTED,
            ])
            ->orderBy('r.dateReservation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réservations en attente
     */
    public function getWaitingReservations(Event $event): array
    {
        return $this->reservationRepository->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.isWaitingList = true')
            ->andWhere('r.statut = :status')
            ->setParameter('event', $event)
            ->setParameter('status', EventReservation::STATUS_PENDING)
            ->orderBy('r.waitingPosition', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
