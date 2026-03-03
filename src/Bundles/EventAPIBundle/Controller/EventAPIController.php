<?php

namespace App\Bundles\EventAPIBundle\Controller;

use App\Bundles\EventCoreBundle\Service\EventService;
use App\Bundles\EventNotificationBundle\Service\EventNotificationService;
use App\Entity\Event;
use App\Entity\EventReservation;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\EventReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API REST pour les événements
 */
#[Route('/api/events', name: 'api_events_')]
class EventAPIController extends AbstractController
{
    public function __construct(
        private EventService $eventService,
        private EventNotificationService $notificationService,
        private EventRepository $eventRepository,
        private EventReservationRepository $reservationRepository,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $query = $request->query->get('q');
        $sortBy = $request->query->get('sort', 'date');
        $category = $request->query->get('category');

        $events = $this->eventService->searchEvents($query, $sortBy, $category);

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn(Event $e) => $this->normalizeEvent($e), $events),
            'count' => count($events),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Event $event): JsonResponse
    {
        $details = $this->eventService->getEventDetails($event);

        return $this->json([
            'status' => 'success',
            'data' => array_merge(
                $this->normalizeEvent($event),
                [
                    'remaining' => $details['remaining'],
                    'activeReservations' => $details['activeReservations'],
                    'isFull' => $details['isFull'],
                ]
            ),
        ]);
    }

    #[Route('/{id}/reserve', name: 'reserve', methods: ['POST'])]
    public function reserve(Event $event, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $notes = $data['notes'] ?? '';

        // Use detailed reservation creation to handle waiting lists
        $result = $this->eventService->createReservationDetailed($event, $user, $notes);

        if ($result['status'] === 'error') {
            return $this->json(
                ['error' => $result['message']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $reservation = $result['reservation'];
        
        // Send appropriate notification based on status
        if ($result['isWaitingList']) {
            $this->notificationService->notifyAddedToWaitingList($reservation, $result['waitingPosition'] ?? 0);
        } else {
            $this->notificationService->notifyReservationConfirmed($reservation);
        }

        // Return 202 Accepted for waiting list, 201 Created for confirmed
        $statusCode = $result['isWaitingList'] ? Response::HTTP_ACCEPTED : Response::HTTP_CREATED;

        return $this->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => [
                'id' => $reservation->getId(),
                'status' => $reservation->getStatut(),
                'isWaitingList' => $result['isWaitingList'],
                'waitingPosition' => $result['waitingPosition'] ?? null,
                'createdAt' => $reservation->getDateReservation()->format('c'),
            ],
        ], $statusCode);
    }

    #[Route('/calendar/data', name: 'calendar', methods: ['GET'])]
    public function getCalendarData(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start', 'now'));
        $endDate = new \DateTime($request->query->get('end', '+12 months'));

        $startDate->modify('first day of this month');
        $endDate->modify('last day of this month');

        $calendarData = $this->eventService->generateCalendarData($startDate, $endDate);

        return $this->json([
            'status' => 'success',
            'data' => $calendarData,
        ]);
    }

    #[Route('/upcoming', name: 'upcoming', methods: ['GET'])]
    public function getUpcoming(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 10);
        $events = $this->eventService->getUpcomingEvents($limit);

        return $this->json([
            'status' => 'success',
            'data' => array_map(fn(Event $e) => $this->normalizeEvent($e), $events),
            'count' => count($events),
        ]);
    }

    /**
     * Normalise un événement pour l'API
     */
    private function normalizeEvent(Event $event): array
    {
        return [
            'id' => $event->getId(),
            'title' => $event->getTitre(),
            'description' => $event->getDescription(),
            'date' => $event->getDateEvent()->format('c'),
            'location' => $event->getLieu(),
            'capacity' => $event->getCapacite(),
            'category' => $event->getCategorie() ?? 'general',
            'image' => $event->getImage(),
        ];
    }
}
