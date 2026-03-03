# Guide d'Intégration des Event Bundles

Ce guide explique comment intégrer les bundles Event dans votre application Symfony existante.

## ✅ Étape 1: Vérifier l'Enregistrement des Bundles

Les bundles sont automatiquement enregistrés dans `config/bundles.php`:

```php
// config/bundles.php
App\Bundles\EventCoreBundle\EventCoreBundle::class => ['all' => true],
App\Bundles\EventUIBundle\EventUIBundle::class => ['all' => true],
App\Bundles\EventAPIBundle\EventAPIBundle::class => ['all' => true],
App\Bundles\EventNotificationBundle\EventNotificationBundle::class => ['all' => true],
```

## ✅ Étape 2: Configurer les Services

Créer ou mettre à jour `config/packages/event_bundles.yaml`:

```yaml
# config/packages/event_bundles.yaml

# Import des configurations des bundles
imports:
  - resource: '../services.yaml'

services:
  # Event Core Services
  App\Bundles\EventCoreBundle\Service\EventService:
    arguments:
      $eventRepository: '@App\Repository\EventRepository'
      $reservationRepository: '@App\Repository\EventReservationRepository'

  # Event Notification Services
  App\Bundles\EventNotificationBundle\Service\EventNotificationService:
    arguments:
      $mailer: '@mailer'
      $logger: '@logger'
      $mailerFrom: '%env(MAILER_FROM)%'

# Configuration du Mailer (si nécessaire)
framework:
  mailer:
    dsn: '%env(MAILER_DSN)%'
```

## ✅ Étape 3: Variables d'Environnement

Ajouter à `.env`:

```env
# .env
MAILER_DSN=smtp://localhost
MAILER_FROM=noreply@mindcare.com
```

## ✅ Étape 4: Routes

Créer ou mettre à jour `config/routes.yaml`:

```yaml
# config/routes.yaml

# Routes EventUI
event_ui:
  resource: '@EventUIBundle/Resources/config/routes.yaml'
  prefix: ''

# Routes EventAPI
event_api:
  resource: '@EventAPIBundle/Resources/config/routes.yaml'
  prefix: /api
```

Ou créer les routes dans chaque bundle:

```yaml
# src/Bundles/EventUIBundle/Resources/config/routes.yaml
front_events_index:
  path: /events
  controller: App\Bundles\EventUIBundle\Controller\EventUIController::index
  methods: [GET]

front_events_show:
  path: /events/{id}
  controller: App\Bundles\EventUIBundle\Controller\EventUIController::show
  methods: [GET]
```

## ✅ Étape 5: Template Twig

Créer le template dans:

```
src/Bundles/EventUIBundle/Resources/views/event/
  ├── index.html.twig
  └── show.html.twig
```

### Option A: Copier depuis l'existant

Si vous avez déjà des templates:

```bash
cp templates/front/event/index.html.twig src/Bundles/EventUIBundle/Resources/views/event/
cp templates/front/event/show.html.twig src/Bundles/EventUIBundle/Resources/views/event/
```

### Option B: Utiliser les chemins de namespace

Mettre à jour le controller pour utiliser le namespace:

```php
return $this->render('@EventUI/event/index.html.twig', [...]);
```

Dans `config/packages/twig.yaml`:

```yaml
twig:
  paths:
    '%kernel.project_dir%/src/Bundles/EventUIBundle/Resources/views': EventUI
    '%kernel.project_dir%/src/Bundles/EventAPIBundle/Resources/views': EventAPI
```

## ✅ Étape 6: Injection de Dépendances

### Dans vos contrôleurs existants

```php
use App\Bundles\EventCoreBundle\Service\EventService;

class YourController extends AbstractController
{
    public function __construct(
        private EventService $eventService
    ) {}

    public function myAction()
    {
        $events = $this->eventService->searchEvents('query', 'date', 'category');
        // ...
    }
}
```

### Dans les services

```php
use App\Bundles\EventCoreBundle\Service\EventService;
use App\Bundles\EventNotificationBundle\Service\EventNotificationService;

class MyService
{
    public function __construct(
        private EventService $eventService,
        private EventNotificationService $notificationService
    ) {}
}
```

## ✅ Étape 7: Migration de l'Ancien Code

### Remplacer les appels directs au repository

**Avant:**
```php
$events = $eventRepository->findBySearch($query, $sortBy, $category);
```

**Après:**
```php
$events = $this->eventService->searchEvents($query, $sortBy, $category);
```

### Remplacer la logique métier

**Avant:**
```php
$activeReservations = $reservationRepository->countActiveByEvent($event);
$remaining = max(0, $event->getCapacite() - $activeReservations);
```

**Après:**
```php
$details = $this->eventService->getEventDetails($event);
$remaining = $details['remaining'];
```

## ✅ Étape 8: Écoutes d'Événements

Les listeners sont automatiquement enregistrés. Les notifications sont envoyées automatiquement lors de:

- Création d'une réservation → `EventReservationListener::postPersist()`
- Annulation d'une réservation → `EventReservationListener::postUpdate()`

## 🔗 Endpoints API

```bash
# Lister les événements
GET http://localhost:8000/api/events

# Détails d'un événement
GET http://localhost:8000/api/events/1

# Réserver un événement
POST http://localhost:8000/api/events/1/reserve
Content-Type: application/json
{"notes": "Optional notes"}

# Données du calendrier
GET http://localhost:8000/api/events/calendar/data?start=2026-02-01&end=2026-12-31

# Événements à venir
GET http://localhost:8000/api/events/upcoming?limit=10
```

## 🧪 Tests

Pour tester les bundles:

```php
// tests/Bundles/EventCoreBundle/Service/EventServiceTest.php
<?php

namespace App\Tests\Bundles\EventCoreBundle\Service;

use App\Bundles\EventCoreBundle\Service\EventService;
use PHPUnit\Framework\TestCase;

class EventServiceTest extends TestCase
{
    private EventService $eventService;

    protected function setUp(): void
    {
        // Setup mock repositories...
    }

    public function testGetEventDetails(): void
    {
        // Test implementation...
    }
}
```

## 🚨 Debug

Vérifier les bundles enregistrés:

```bash
symfony console debug:container --tag=controller.service_arguments
```

Vérifier les services disponibles:

```bash
symfony console debug:container | grep -i event
```

Vérifier les routes:

```bash
symfony console debug:router | grep -i event
```

## 📊 Diagramme d'Intégration

```
┌─────────────────────────────────────┐
│   Application Principale            │
├─────────────────────────────────────┤
│                                     │
│  ┌──────────────────────────────┐  │
│  │ EventUIBundle                │  │
│  │ (Web Interface)              │  │
│  └────────────┬─────────────────┘  │
│               │ uses services       │
│  ┌────────────▼─────────────────┐  │
│  │ EventCoreBundle              │  │
│  │ (Business Logic)             │  │
│  │ - EventService               │  │ 
│  └────────────┬─────────────────┘  │
│               │ triggeres events    │
│  ┌────────────▼─────────────────┐  │
│  │ EventNotificationBundle      │  │
│  │ (Notifications)              │  │
│  │ - EventNotificationService   │  │
│  └─────────────────────────────┘  │
│   ↓                                 │
│  ┌──────────────────────────────┐  │
│  │ EventAPIBundle               │  │
│  │ (REST API)                   │  │
│  └──────────────────────────────┘  │
└─────────────────────────────────────┘
         ↓
    Database
```

## ✅ Checklist de Déploiement

- [ ] Bundles enregistrés dans `config/bundles.php`
- [ ] Services configurés dans `config/services.yaml`
- [ ] Variables d'environnement définies
- [ ] Templates Twig placés dans le bon répertoire
- [ ] Routes configurées
- [ ] Dépendances injectées correctement
- [ ] Tests exécutés
- [ ] Ancien code migré
- [ ] Cache vidé: `symfony console cache:clear`

## 📞 Support

Pour plus d'informations sur chaque bundle, consultez:
- [README.md](./README.md) - Documentation générale
- `src/Bundles/EventCoreBundle/` - Services métier
- `src/Bundles/EventUIBundle/` - Interface utilisateur
- `src/Bundles/EventAPIBundle/` - API REST
- `src/Bundles/EventNotificationBundle/` - Notifications
