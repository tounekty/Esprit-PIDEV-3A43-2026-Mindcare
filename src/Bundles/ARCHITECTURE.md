# Architecture des Event Bundles

## Vue d'ensemble

```
src/Bundles/
├── EventCoreBundle/              ← Cœur métier
│   ├── EventCoreBundle.php
│   ├── Entity/                   (références entities existantes)
│   ├── Repository/               (références repositories existants)
│   ├── Service/
│   │   └── EventService.php      ← Logique métier centralisée
│   └── Resources/
│       └── config/
│           └── services.yaml
│
├── EventUIBundle/                ← Interface Web
│   ├── EventUIBundle.php
│   ├── Controller/
│   │   └── EventUIController.php ← Controllers web
│   ├── Resources/
│   │   ├── config/
│   │   │   └── services.yaml
│   │   └── views/
│   │       └── event/
│   │           ├── index.html.twig
│   │           └── show.html.twig
│   └── DependencyInjection/      (optionnel)
│
├── EventAPIBundle/               ← API REST
│   ├── EventAPIBundle.php
│   ├── Controller/
│   │   └── EventAPIController.php ← Endpoints REST
│   └── Resources/
│       └── config/
│           └── services.yaml
│
├── EventNotificationBundle/      ← Notifications
│   ├── EventNotificationBundle.php
│   ├── Service/
│   │   └── EventNotificationService.php ← Service de notification
│   ├── EventListener/
│   │   └── EventReservationListener.php ← Listeners d'événements Doctrine
│   └── Resources/
│       └── config/
│           └── services.yaml
│
├── README.md                     ← Documentation générale
├── INTEGRATION.md                ← Guide d'intégration
├── ROUTES.md                     ← Configuration des routes
├── EXAMPLES.md                   ← Exemples d'utilisation
└── ARCHITECTURE.md               ← Ce fichier
```

## Principes de Design

### 1. Separation of Concerns (SoC)
- **EventCoreBundle** = Logique métier uniquement
- **EventUIBundle** = Présentation web uniquement
- **EventAPIBundle** = Interface API uniquement
- **EventNotificationBundle** = Notifications uniquement

### 2. Single Responsibility Principle (SRP)
Chaque service a une seule responsabilité:
- `EventService` → gère les opérations sur les événements
- `EventNotificationService` → envoie les notifications
- Controllers → routent les requêtes

### 3. Dependency Injection
Tous les services utilisent l'injection de dépendances Symfony:
```php
public function __construct(
    private EventService $eventService,
    private EventNotificationService $notificationService
) {}
```

## Dépendances Entre Bundles

```
┌─────────────────────────────────────┐
│   EventUIBundle / EventAPIBundle     │  (Presenters)
│   (Controllers + Routes)             │
└──────────────────┬──────────────────┘
                   │ uses
┌──────────────────▼──────────────────┐
│    EventCoreBundle                   │  (Domain Logic)
│    (EventService + Entities)         │
└──────────────────┬──────────────────┘
                   │ triggers events
┌──────────────────▼──────────────────┐
│ EventNotificationBundle              │  (Side Effects)
│ (Notifications + EventListeners)     │
└─────────────────────────────────────┘
```

### Direction des dépendances (inversion de contrôle)
- UI/API ne connaît que EventCoreBundle
- EventCoreBundle ne connaît ni UI ni API
- EventNotificationBundle s'enregistre comme listener
- Chaque bundle est indépendant

## Services par Bundle

### EventCoreBundle
```
EventService (Primary Service)
├── getEventDetails(event)
├── createReservation(event, user, notes)
├── cancelReservation(reservation)
├── acceptReservation(reservation)
├── generateCalendarData(startDate, endDate)
├── searchEvents(query, sortBy, category)
└── getUpcomingEvents(limit)
```

### EventNotificationBundle
```
EventNotificationService
├── notifyReservationConfirmed(reservation)
├── notifyReservationCancelled(reservation)
└── notifyEventAvailableSpots(event)

EventReservationListener
├── postPersist() → Création → Envoie confirmation
└── postUpdate() → Modification → Envoie annulation
```

### EventUIBundle
```
EventUIController
├── index(request) → GET /events
└── show(event) → GET /events/{id}
```

### EventAPIBundle
```
EventAPIController
├── index(request) → GET /api/events
├── show(event) → GET /api/events/{id}
├── reserve(event, request) → POST /api/events/{id}/reserve
├── getCalendarData(request) → GET /api/events/calendar/data
└── getUpcoming(request) → GET /api/events/upcoming
```

## Flux de Données

### Créer une réservation

```
User Browser/API
    ↓
EventUIController / EventAPIController
    ↓
EventService::createReservation()
    ↓
EventReservation Entity + Repository
    ↓
Database
    ↓
EventReservationListener::postPersist()
    ↓
EventNotificationService::notifyReservationConfirmed()
    ↓
Mailer → Email
    ↓
User Email
```

### Afficher le calendrier

```
User Browser
    ↓
EventUIController::index()
    ↓
EventService::generateCalendarData()
    ↓
EventRepository::findByDateRange()
    ↓
Database
    ↓
Processed Calendar Data
    ↓
Template View
    ↓
HTML to Browser
```

## Extensibilité

### Ajouter un nouveau service de notification (SMS, Push)
1. Créer un nouveau listener dans EventNotificationBundle
2. Implémenter `postPersist` / `postUpdate`
3. Envoyer SMS/Push via service externe
4. EventCoreBundle reste inchangé

### Ajouter un nouveau controller UI (Mobile App)
1. Créer MobileEventController dans EventUIBundle
2. Même logique que EventUIController
3. Retourner JSON au lieu de HTML
4. EventCoreBundle reste inchangé

### Ajouter un nouveau endpoint API
1. Ajouter une nouvelle méthode dans EventAPIController
2. Utiliser les services d'EventCoreBundle
3. Retourner JSON
4. Pas besoin de modifier les autres bundles

## Configuration

### Enregistrement dans Symfony
```php
// config/bundles.php
App\Bundles\EventCoreBundle\EventCoreBundle::class => ['all' => true],
App\Bundles\EventUIBundle\EventUIBundle::class => ['all' => true],
App\Bundles\EventAPIBundle\EventAPIBundle::class => ['all' => true],
App\Bundles\EventNotificationBundle\EventNotificationBundle::class => ['all' => true],
```

### Services Yaml
Chaque bundle a son propre `services.yaml`:
- Enregistre ses services
- Configure l'autowiring
- Les listeners Doctrine

### Twig Namespace
```yaml
# config/packages/twig.yaml
twig:
  paths:
    '%kernel.project_dir%/src/Bundles/EventUIBundle/Resources/views': EventUI
```

## Avantages de cette architecture

✅ **Modulaire** - Chaque bundle est indépendant  
✅ **Testable** - Facile d'écrire des tests unitaires  
✅ **Réutilisable** - Peut être utilisé dans d'autres projets  
✅ **Maintenable** - Chaque bundle a une responsabilité claire  
✅ **Scalable** - Facile d'ajouter de nouvelles fonctionnalités  
✅ **Flexible** - Changer l'implémentation sans affecter le reste  

## Limitations actuelles

⚠️ Templates Twig doivent être copiées manuellement  
⚠️ Pas d'interface utilisateur pour la gestion des bundles  
⚠️ Configuration manuelle des routes requise  

## Améliorations Futures

🔧 Créer des command Symfony pour initialiser les bundles  
🔧 Ajouter des traits pour la réutilisation de code  
🔧 Implémenter les événements Symfony pour plus de flexibilité  
🔧 Ajouter une coverage de tests complète  
🔧 Documenter les hooks d'extension  

## Déploiement

### En tant que bundles locaux
Simplement inclure dans le projet principal (actuellement)

### En tant que packages composer
```json
{
    "require": {
        "mindcare/event-core-bundle": "^1.0",
        "mindcare/event-ui-bundle": "^1.0",
        "mindcare/event-api-bundle": "^1.0",
        "mindcare/event-notification-bundle": "^1.0"
    }
}
```

Chaque bundle peut être:
1. Publié sur Packagist
2. Versionnée indépendamment
3. Réutilisée dans d'autres projets
4. Maintenue par l'équipe

## Références

- [Symfony Bundles Best Practices](https://symfony.com/doc/current/quick_tour/the_architecture.html)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Dependency Injection Pattern](https://en.wikipedia.org/wiki/Dependency_injection)
