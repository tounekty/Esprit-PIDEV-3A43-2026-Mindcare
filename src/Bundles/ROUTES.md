# Routes for Event Bundles

## Route Files

### src/Bundles/EventUIBundle/Resources/config/routes.yaml
```yaml
front_events_index:
  path: /events
  controller: App\Bundles\EventUIBundle\Controller\EventUIController::index
  name: front_events_index
  methods: [GET]

front_events_show:
  path: /events/{id}
  controller: App\Bundles\EventUIBundle\Controller\EventUIController::show
  name: front_events_show
  methods: [GET]
  requirements:
    id: '\d+'
```

### src/Bundles/EventAPIBundle/Resources/config/routes.yaml
```yaml
api_events_index:
  path: /api/events
  controller: App\Bundles\EventAPIBundle\Controller\EventAPIController::index
  name: api_events_index
  methods: [GET]

api_events_show:
  path: /api/events/{id}
  controller: App\Bundles\EventAPIBundle\Controller\EventAPIController::show
  name: api_events_show
  methods: [GET]
  requirements:
    id: '\d+'

api_events_reserve:
  path: /api/events/{id}/reserve
  controller: App\Bundles\EventAPIBundle\Controller\EventAPIController::reserve
  name: api_events_reserve
  methods: [POST]
  requirements:
    id: '\d+'

api_events_calendar:
  path: /api/events/calendar/data
  controller: App\Bundles\EventAPIBundle\Controller\EventAPIController::getCalendarData
  name: api_events_calendar
  methods: [GET]

api_events_upcoming:
  path: /api/events/upcoming
  controller: App\Bundles\EventAPIBundle\Controller\EventAPIController::getUpcoming
  name: api_events_upcoming
  methods: [GET]
```

## Configuration dans config/routes.yaml

```yaml
# config/routes.yaml

# Framework routes
framework:
  resource: '@FrameworkBundle/Resources/config/routing/errors.xml'
  prefix: /_error

# Web profiler routes (development)
when@dev:
  web_profiler_wdt:
    resource: '@WebProfilerBundle/Resources/config/routing/wdt.xml'
    prefix: /_wdt

  web_profiler_profiler:
    resource: '@WebProfilerBundle/Resources/config/routing/profiler.xml'
    prefix: /_profiler

  web_profiler_router:
    resource: '@WebProfilerBundle/Resources/config/routing/router.xml'
    prefix: /_profiler/router

# Application routes
app:
  resource: app/
  type: directory
  prefix: ''

# Security routes
app_security:
  resource: 'routes/security.yaml'
  prefix: ''

# Admin routes
app_admin:
  resource: 'routes/framework.yaml'
  prefix: /admin

# Event UI Bundle Routes
event_ui:
  resource: '../Bundles/EventUIBundle/Resources/config/routes.yaml'
  prefix: ''

# Event API Bundle Routes
event_api:
  resource: '../Bundles/EventAPIBundle/Resources/config/routes.yaml'
  prefix: ''
```

## Annotation Routes

Alternatively, you can use annotations in the controllers:

```php
// src/Bundles/EventUIBundle/Controller/EventUIController.php

#[Route('/events', name: 'front_events_index', methods: ['GET'])]
public function index(Request $request): Response
{
    // ...
}

#[Route('/events/{id}', name: 'front_events_show', methods: ['GET'])]
public function show(Event $event): Response
{
    // ...
}
```

```php
// src/Bundles/EventAPIBundle/Controller/EventAPIController.php

#[Route('/api/events', name: 'api_events_', methods: ['GET'])]
class EventAPIController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse { }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Event $event): JsonResponse { }

    #[Route('/{id}/reserve', name: 'reserve', methods: ['POST'])]
    public function reserve(Event $event, Request $request): JsonResponse { }
}
```

## Checking Routes

```bash
# List all routes
symfony console debug:router

# Filter event routes
symfony console debug:router | grep event

# Show details of a route
symfony console debug:router front_events_index
```

## URL Patterns

### UI Routes
- `/events` → List events
- `/events/1` → Show event 1

### API Routes
- `/api/events` → List events (JSON)
- `/api/events/1` → Show event 1 (JSON)
- `/api/events/1/reserve` → Reserve event 1 (POST)
- `/api/events/calendar/data?start=2026-01-01&end=2026-12-31` → Calendar data
- `/api/events/upcoming?limit=10` → Upcoming events
