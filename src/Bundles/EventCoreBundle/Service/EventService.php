<?php

namespace App\Bundles\EventCoreBundle\Service;

use App\Entity\Event;
use App\Entity\EventReservation;
use App\Repository\EventRepository;
use App\Repository\EventReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service Core pour la gestion des événements
 * 
 * Intègre CapacityManager pour la gestion intelligente des places
 */
class EventService
{
    private EventRepository $eventRepository;
    private EventReservationRepository $reservationRepository;
    private EntityManagerInterface $entityManager;
    private CapacityManager $capacityManager;

    public function __construct(
        EventRepository $eventRepository,
        EventReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        CapacityManager $capacityManager
    ) {
        $this->eventRepository = $eventRepository;
        $this->reservationRepository = $reservationRepository;
        $this->entityManager = $entityManager;
        $this->capacityManager = $capacityManager;
    }

    /**
     * Récupère les détails d'un événement avec les réservations
     */
    public function getEventDetails(Event $event): array
    {
        $stats = $this->capacityManager->getCapacityStats($event);

        return [
            'event' => $event,
            'remaining' => $stats['available'],
            'activeReservations' => $stats['confirmed'],
            'waitingCount' => $stats['waiting'],
            'isFull' => $stats['isFull'],
            'status' => $stats['status'],
            'capacityStats' => $stats,
        ];
    }

    /**
     * Crée une nouvelle réservation d'événement
     * Retourne la réservation ou null si erreur
     * 
     * Ajoute automatiquement à la liste d'attente si complet
     */
    public function createReservation(Event $event, mixed $user, string $notes = ''): ?EventReservation
    {
        $result = $this->capacityManager->createReservationOrWaiting($event, $user, $notes);
        return $result['reservation'] ?? null;
    }

    /**
     * Version avancée qui retourne les détails
     */
    public function createReservationDetailed(Event $event, mixed $user, string $notes = ''): array
    {
        return $this->capacityManager->createReservationOrWaiting($event, $user, $notes);
    }

    /**
     * Annule une réservation et promeut automatiquement le premier en attente
     */
    public function cancelReservation(EventReservation $reservation): bool
    {
        $result = $this->capacityManager->cancelReservationAndPromote($reservation);
        return $result['reservation'] !== null;
    }

    /**
     * Version avancée qui retourne les détails
     */
    public function cancelReservationDetailed(EventReservation $reservation): array
    {
        return $this->capacityManager->cancelReservationAndPromote($reservation);
    }

    /**
     * Accepte une réservation
     */
    public function acceptReservation(EventReservation $reservation): bool
    {
        return $this->capacityManager->acceptReservation($reservation);
    }

    /**
     * Refuse une réservation
     */
    public function refuseReservation(EventReservation $reservation): bool
    {
        return $this->capacityManager->refuseReservation($reservation);
    }

    /**
     * Promeut une réservation de la liste d'attente
     */
    public function promoteFromWaiting(EventReservation $reservation): bool
    {
        return $this->capacityManager->promoteFromWaiting($reservation);
    }

    /**
     * Génère les données du calendrier pour la plage de dates
     */
    public function generateCalendarData(\DateTime $startDate, \DateTime $endDate): array
    {
        $currentDay = clone $startDate;
        $calendarData = [];

        while ($currentDay <= $endDate) {
            $dateKey = $currentDay->format('Y-m-d');
            $calendarData[$dateKey] = [
                'date' => clone $currentDay,
                'total' => 0,
                'available' => 0,
                'events' => 0,
            ];
            $currentDay->modify('+1 day');
        }

        // Récupère les événements dans la plage
        $events = $this->eventRepository->createQueryBuilder('e')
            ->where('e.dateEvent >= :startDate')
            ->andWhere('e.dateEvent <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();

        // Construit les données du calendrier
        foreach ($events as $event) {
            $dateKey = $event->getDateEvent()->format('Y-m-d');
            if (!isset($calendarData[$dateKey])) {
                $calendarData[$dateKey] = [
                    'date' => $event->getDateEvent(),
                    'total' => 0,
                    'available' => 0,
                    'events' => 0,
                ];
            }
            $activeReservations = $this->reservationRepository->countActiveByEvent($event);
            $calendarData[$dateKey]['total'] += $event->getCapacite();
            $calendarData[$dateKey]['available'] += max(0, $event->getCapacite() - $activeReservations);
            $calendarData[$dateKey]['events'] += 1;
        }

        ksort($calendarData);
        return $calendarData;
    }

    /**
     * Récupère les événements avec recherche et filtres
     */
    public function searchEvents(
        ?string $query = null,
        string $sortBy = 'date',
        ?string $category = null
    ): array {
        return $this->eventRepository->findBySearch($query, $sortBy, $category);
    }

    /**
     * Récupère les événements à venir
     */
    public function getUpcomingEvents(int $limit = 10): array
    {
        $now = new \DateTime();
        return $this->eventRepository->createQueryBuilder('e')
            ->where('e.dateEvent >= :now')
            ->setParameter('now', $now)
            ->orderBy('e.dateEvent', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
