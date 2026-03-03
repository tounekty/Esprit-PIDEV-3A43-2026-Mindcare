# Exemples d'Utilisation des Event Bundles

## 1. EventCoreBundle - Exemples d'Utilisation

### 1.1 Récupérer les détails d'un événement

```php
use App\Bundles\EventCoreBundle\Service\EventService;
use App\Entity\Event;

class MyController extends AbstractController
{
    public function __construct(
        private EventService $eventService
    ) {}

    public function details(Event $event): Response
    {
        $details = $this->eventService->getEventDetails($event);
        
        return $this->render('event/details.html.twig', [
            'event' => $event,
            'remaining' => $details['remaining'],
            'activeReservations' => $details['activeReservations'],
            'isFull' => $details['isFull'],
        ]);
    }
}
```

### 1.2 Créer une réservation

```php
use App\Bundles\EventCoreBundle\Service\EventService;

public function reserve(Event $event, EventService $eventService): Response
{
    $user = $this->getUser();
    
    $reservation = $eventService->createReservation(
        event: $event,
        user: $user,
        notes: 'Mon intérêt pour cet événement'
    );

    if ($reservation) {
        return new JsonResponse(['status' => 'success'], Response::HTTP_CREATED);
    }
    
    return new JsonResponse(['error' => 'Event is full'], Response::HTTP_BAD_REQUEST);
}
```

### 1.3 Générer les données du calendrier

```php
use App\Bundles\EventCoreBundle\Service\EventService;

public function calendarData(EventService $eventService): Response
{
    $now = new \DateTime();
    $startDate = clone $now;
    $startDate->modify('first day of this month');
    
    $endDate = clone $now;
    $endDate->modify('+12 months');
    $endDate->modify('last day of this month');

    $calendarData = $eventService->generateCalendarData($startDate, $endDate);

    return $this->render('event/calendar.html.twig', [
        'calendarData' => $calendarData,
    ]);
}
```

### 1.4 Rechercher des événements

```php
use App\Bundles\EventCoreBundle\Service\EventService;

public function search(
    EventService $eventService,
    Request $request
): Response
{
    $query = $request->query->get('q');
    $sortBy = $request->query->get('sort', 'date');
    $category = $request->query->get('category');

    $events = $eventService->searchEvents($query, $sortBy, $category);

    return $this->json([
        'count' => count($events),
        'events' => $events,
    ]);
}
```

### 1.5 Récupérer les événements à venir

```php
use App\Bundles\EventCoreBundle\Service\EventService;

public function upcomingEvents(EventService $eventService): Response
{
    $events = $eventService->getUpcomingEvents(limit: 5);

    return $this->render('event/upcoming.html.twig', [
        'events' => $events,
    ]);
}
```

## 2. EventNotificationBundle - Exemples d'Utilisation

### 2.1 Envoyer une notification de confirmation

```php
use App\Bundles\EventNotificationBundle\Service\EventNotificationService;
use App\Entity\EventReservation;

class ReservationService
{
    public function __construct(
        private EventNotificationService $notificationService
    ) {}

    public function processReservation(EventReservation $reservation): void
    {
        // ... logique métier ...

        // Envoyer une notification
        $sent = $this->notificationService->notifyReservationConfirmed($reservation);
        
        if ($sent) {
            // Log success
        }
    }
}
```

### 2.2 Envoyer une notification d'annulation

```php
use App\Bundles\EventNotificationBundle\Service\EventNotificationService;
use App\Entity\EventReservation;

public function cancelReservation(
    EventReservation $reservation,
    EventNotificationService $notificationService
): Response
{
    $reservation->setStatut(EventReservation::STATUS_CANCELLED);
    
    // Entity manager flush...
    
    $notificationService->notifyReservationCancelled($reservation);

    return new JsonResponse(['status' => 'cancelled']);
}
```

### 2.3 Les notifications automatiques

Les notifications sont automatiquement envoyées via `EventReservationListener`:

```php
// Automatique lors de la création d'une réservation
$reservation = new EventReservation();
$reservation->setStatut(EventReservation::STATUS_PENDING);
$entityManager->persist($reservation);
$entityManager->flush(); // TextNotification est envoyée automatiquement

// Automatique lors de l'annulation
$reservation->setStatut(EventReservation::STATUS_CANCELLED);
$entityManager->flush(); // Notification d'annulation envoyée automatiquement
```

## 3. EventUIBundle - Exemples d'Utilisation

### 3.1 Afficher la liste des événements avec calendrier

```php
// Automatiquement fourni par EventUIController::index()
// Route: GET /events

// Dans la vue:
{% extends 'base.html.twig' %}

{% block body %}
    <div class="events-container">
        <!-- Calendar -->
        <div class="calendar">
            {% for dateKey, data in calendarData %}
                <div class="day available-{{ data.available }}">
                    {{ data.date|date('d') }}
                    <span>{{ data.available }} options</span>
                </div>
            {% endfor %}
        </div>

        <!-- Events list -->
        <div class="events-list">
            {% for event in events %}
                <div class="event-card">
                    <h3>{{ event.titre }}</h3>
                    <p>{{ event.description }}</p>
                    <a href="{{ path('front_events_show', {id: event.id}) }}">
                        Voir les détails
                    </a>
                </div>
            {% endfor %}
        </div>
    </div>
{% endblock %}
```

### 3.2 Afficher les détails d'un événement

```php
// Automatiquement fourni par EventUIController::show()
// Route: GET /events/{id}

// Dans la vue:
{% extends 'base.html.twig' %}

{% block body %}
    <div class="event-details">
        <h1>{{ event.titre }}</h1>
        
        <div class="event-info">
            <p><strong>Date:</strong> {{ event.dateEvent|date('d/m/Y H:i') }}</p>
            <p><strong>Lieu:</strong> {{ event.lieu }}</p>
            <p><strong>Capacité:</strong> {{ event.capacite }} personnes</p>
            <p><strong>Places restantes:</strong> {{ remaining }}</p>
        </div>

        <div class="event-description">
            {{ event.description }}
        </div>

        {% if not isFull %}
            <form method="POST" action="{{ path('api_events_reserve', {id: event.id}) }}">
                <button type="submit">Réserver une place</button>
            </form>
        {% else %}
            <p class="text-red-500">Cet événement est complet.</p>
        {% endif %}
    </div>
{% endblock %}
```

## 4. EventAPIBundle - Exemples d'Utilisation

### 4.1 Lister les événements via API

```bash
# Request
GET /api/events?q=yoga&category=wellness&sort=date

# Response
{
    "status": "success",
    "count": 3,
    "data": [
        {
            "id": 1,
            "title": "Yoga matinal",
            "description": "Séance de yoga relaxante",
            "date": "2026-03-15T09:00:00+00:00",
            "location": "Salle A",
            "capacity": 20,
            "category": "wellness",
            "image": "/images/yoga.jpg"
        }
    ]
}
```

### 4.2 Obtenir les détails d'un événement

```bash
# Request
GET /api/events/1

# Response
{
    "status": "success",
    "data": {
        "id": 1,
        "title": "Yoga matinal",
        "description": "Séance de yoga relaxante",
        "date": "2026-03-15T09:00:00+00:00",
        "location": "Salle A",
        "capacity": 20,
        "category": "wellness",
        "image": "/images/yoga.jpg",
        "remaining": 15,
        "activeReservations": 5,
        "isFull": false
    }
}
```

### 4.3 Créer une réservation via API

```bash
# Request
POST /api/events/1/reserve
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
    "notes": "Je suis très intéressé par cet événement"
}

# Response
{
    "status": "success",
    "message": "Reservation created",
    "data": {
        "id": 42,
        "status": "pending",
        "createdAt": "2026-02-24T12:30:00+00:00"
    }
}
```

### 4.4 Obtenir les données du calendrier

```bash
# Request
GET /api/events/calendar/data?start=2026-02-01&end=2026-12-31

# Response
{
    "status": "success",
    "data": {
        "2026-02-24": {
            "date": "2026-02-24T00:00:00+00:00",
            "total": 40,
            "available": 15,
            "events": 2
        },
        "2026-02-25": {
            "date": "2026-02-25T00:00:00+00:00",
            "total": 60,
            "available": 60,
            "events": 3
        }
    }
}
```

### 4.5 Obtenir les événements à venir

```bash
# Request
GET /api/events/upcoming?limit=5

# Response
{
    "status": "success",
    "count": 5,
    "data": [
        {
            "id": 1,
            "title": "Yoga matinal",
            "description": "Séance de yoga relaxante",
            "date": "2026-03-15T09:00:00+00:00",
            "location": "Salle A",
            "capacity": 20,
            "category": "wellness",
            "image": "/images/yoga.jpg"
        }
    ]
}
```

## 5. Cas d'Usage Complets

### 5.1 Flow de réservation complet

```php
// 1. Récupérer l'événement
$event = $eventRepository->find($eventId);

// 2. Vérifier les détails et la disponibilité
$details = $eventService->getEventDetails($event);
if ($details['isFull']) {
    throw new Exception('Événement complet');
}

// 3. Créer la réservation
$reservation = $eventService->createReservation(
    event: $event,
    user: $this->getUser(),
    notes: 'Réservation via le site'
);

// 4. La notification est envoyée automatiquement par EventReservationListener

// 5. Retourner une réponse
return $this->json([
    'status' => 'success',
    'reservationId' => $reservation->getId(),
]);
```

### 5.2 Flow d'affichage du calendrier

```php
// 1. Calculer les dates
$now = new \DateTime();
$startDate = clone $now;
$startDate->modify('first day of this month');
$endDate = clone $now;
$endDate->modify('+12 months');
$endDate->modify('last day of this month');

// 2. Générer les données du calendrier
$calendarData = $eventService->generateCalendarData($startDate, $endDate);

// 3. Passer les données au template
return $this->render('event/calendar.html.twig', [
    'calendarData' => $calendarData,
    'startDate' => $startDate,
    'endDate' => $endDate,
]);

// 4. Dans le template, afficher le calendrier avec les disponibilités
```

## 6. Integration avec JavaScript/Frontend

### 6.1 Appel API avec fetch

```javascript
// Récupérer les événements
async function fetchEvents() {
    const response = await fetch('/api/events?q=yoga');
    const data = await response.json();
    console.log(data.data);
}

// Créer une réservation
async function createReservation(eventId) {
    const response = await fetch(`/api/events/${eventId}/reserve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            notes: 'Je souhaite participer'
        })
    });
    
    if (response.ok) {
        const data = await response.json();
        console.log('Réservation créée:', data);
    }
}

// Récupérer les données du calendrier
async function fetchCalendarData() {
    const response = await fetch('/api/events/calendar/data?start=2026-02-01&end=2026-12-31');
    const data = await response.json();
    displayCalendar(data.data);
}
```

### 6.2 Intégration avec SPA (Vue.js, React, etc.)

```javascript
// composable/useEvents.js
import { ref } from 'vue';

export function useEvents() {
    const events = ref([]);
    const loading = ref(false);

    async function fetchEvents(query = '') {
        loading.value = true;
        try {
            const res = await fetch(`/api/events?q=${query}`);
            const data = await res.json();
            events.value = data.data;
        } finally {
            loading.value = false;
        }
    }

    async function reserveEvent(eventId, notes = '') {
        const res = await fetch(`/api/events/${eventId}/reserve`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notes })
        });
        return res.json();
    }

    return {
        events,
        loading,
        fetchEvents,
        reserveEvent
    };
}
```
