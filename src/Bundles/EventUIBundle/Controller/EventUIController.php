<?php

namespace App\Bundles\EventUIBundle\Controller;

use App\Bundles\EventCoreBundle\Service\EventService;
use App\Entity\Event;
use App\Entity\EventReservation;
use App\Form\EventReservationType;
use App\Repository\EventRepository;
use App\Repository\EventReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller UI pour l'affichage des événements
 */
class EventUIController extends AbstractController
{
    public function __construct(
        private EventService $eventService,
        private EventRepository $eventRepository,
        private EventReservationRepository $reservationRepository,
    ) {}

    #[Route('/events', name: 'front_events_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $sortBy = $request->query->get('sort', 'date');
        $category = $request->query->get('category', '');
        $dateFilter = $request->query->get('date', '');

        // Validate sort parameter
        if (!in_array($sortBy, ['date', 'lieu', 'date_desc'], true)) {
            $sortBy = 'date';
        }

        // Search events
        $events = $this->eventService->searchEvents(
            $query !== '' ? $query : null,
            $sortBy,
            $category !== '' ? $category : null
        );

        // Filter by date if provided
        if ($dateFilter !== '') {
            try {
                $filterDate = new \DateTime($dateFilter);
                $filterDateEnd = clone $filterDate;
                $filterDateEnd->setTime(23, 59, 59);

                $events = array_filter($events, function($event) use ($filterDate, $filterDateEnd) {
                    $eventDate = $event->getDateEvent();
                    return $eventDate >= $filterDate && $eventDate <= $filterDateEnd;
                });
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }

        // Get remaining places for each event
        $remainingById = [];
        foreach ($events as $event) {
            $activeCount = $this->reservationRepository->countActiveByEvent($event);
            $remainingById[$event->getId()] = max(0, $event->getCapacite() - $activeCount);
        }

        // Generate calendar data for 12 months
        $now = new \DateTime();
        $startDate = clone $now;
        $startDate->modify('first day of this month');
        $endDate = clone $now;
        $endDate->modify('+12 months');
        $endDate->modify('last day of this month');

        $calendarData = $this->eventService->generateCalendarData($startDate, $endDate);

        return $this->render('@EventUI/event/index.html.twig', [
            'events' => $events,
            'q' => $query,
            'sortBy' => $sortBy,
            'category' => $category,
            'date' => $dateFilter,
            'remainingById' => $remainingById,
            'calendarData' => $calendarData,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    #[Route('/events/{id}', name: 'front_events_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        $details = $this->eventService->getEventDetails($event);

        return $this->render('@EventUI/event/show.html.twig', [
            'event' => $event,
            'remaining' => $details['remaining'],
            'activeReservations' => $details['activeReservations'],
            'isFull' => $details['isFull'],
        ]);
    }
}
