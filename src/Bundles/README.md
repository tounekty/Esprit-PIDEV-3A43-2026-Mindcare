# Event Bundles - Documentation

Cet ensemble de bundles Symfony modulaires gère la fonctionnalité complète des événements dans l'application MindCare.

## 📦 Structure des Bundles

### 1. **EventCoreBundle**
Le cœur métier des événements. Contient:
- **Services**: `EventService` - Logique métier des événements
  - `getEventDetails()` - Récupère les détails d'un événement
  - `createReservation()` - Crée une réservation
  - `cancelReservation()` - Annule une réservation
  - `generateCalendarData()` - Génère les données du calendrier
  - `searchEvents()` - Recherche avec filtres
  - `getUpcomingEvents()` - Récupère les événements à venir

- **Responsabilités**:
  - Gestion des entités Event et EventReservation
  - Logique métier (réservations, disponibilités)
  - Génération de données pour l'affichage

### 2. **EventUIBundle**
L'interface utilisateur web. Contient:
- **Controllers**: `EventUIController`
  - `index()` - Liste les événements avec calendrier
  - `show()` - Affiche les détails d'un événement

- **Resources/views/**: Templates Twig
  - `event/index.html.twig` - Liste des événements
  - `event/show.html.twig` - Détails d'un événement

- **Responsabilités**:
  - Affichage web des événements
  - Rendu des templates
  - Gestion de l'interface utilisateur

### 3. **EventAPIBundle**
API REST pour les événements. Contient:
- **Controllers**: `EventAPIController`
  - `GET /api/events` - Liste les événements (JSON)
  - `GET /api/events/{id}` - Détails d'un événement (JSON)
  - `POST /api/events/{id}/reserve` - Crée une réservation
  - `GET /api/events/calendar/data` - Données du calendrier
  - `GET /api/events/upcoming` - Événements à venir

- **Responsabilités**:
  - Endpoints REST JSON
  - Sérialisation des données
  - Authentification API

### 4. **EventNotificationBundle**
Gestion des notifications. Contient:
- **Services**: `EventNotificationService`
  - `notifyReservationConfirmed()` - Envoie email de confirmation
  - `notifyReservationCancelled()` - Envoie email d'annulation
  - `notifyEventAvailableSpots()` - Notifie les places disponibles

- **EventListeners**: `EventReservationListener`
  - Écoute les changements de réservations
  - Déclenche les notifications automatiquement

- **Responsabilités**:
  - Notifications par email
  - Logs des actions
  - Intégration avec le mailer de Symfony

## 🔄 Flux d'Utilisation

```
Utilisateur
    ↓
EventUIController (EventUIBundle)
    ↓
EventService (EventCoreBundle)
    ↓
Repositories (Entity Layer)
    ↓
Database
    ↓
EventNotificationService (EventNotificationBundle)
    ↓
Mailer → Email notification
```

## 🚀 Utilisation dans le Contrôleur Principal

```php
// Example: Depuis votre contrôleur principal
$eventService = $container->get(EventService::class);

// Récupère les événements
$events = $eventService->searchEvents($query, 'date', $category);

// Génère les données du calendrier
$calendarData = $eventService->generateCalendarData($start, $end);

// Crée une réservation
$reservation = $eventService->createReservation($event, $user, $notes);
```

## 📋 Routes API

```
GET    /api/events              - Liste tous les événements
GET    /api/events/{id}         - Détails d'un événement
POST   /api/events/{id}/reserve - Réserve un événement
GET    /api/events/calendar/data - Données du calendrier
GET    /api/events/upcoming     - Événements à venir
```

## 🔧 Configuration

Chaque bundle a son propre fichier `services.yaml`:
- `src/Bundles/EventCoreBundle/Resources/config/services.yaml`
- `src/Bundles/EventUIBundle/Resources/config/services.yaml`
- `src/Bundles/EventAPIBundle/Resources/config/services.yaml`
- `src/Bundles/EventNotificationBundle/Resources/config/services.yaml`

### Enregistrer les bundles dans `config/bundles.php`:

```php
return [
    // ... autres bundles
    App\Bundles\EventCoreBundle\EventCoreBundle::class => ['all' => true],
    App\Bundles\EventUIBundle\EventUIBundle::class => ['all' => true],
    App\Bundles\EventAPIBundle\EventAPIBundle::class => ['all' => true],
    App\Bundles\EventNotificationBundle\EventNotificationBundle::class => ['all' => true],
];
```

## 📝 Variables d'Environnement

`.env`:
```
MAILER_FROM=noreply@mindcare.com
```

## 🧪 Exemple d'Utilisation

### Créer une réservation avec notification:

```php
use App\Bundles\EventCoreBundle\Service\EventService;
use App\Bundles\EventNotificationBundle\Service\EventNotificationService;

// Dans un controller
public function reserve(Event $event, EventService $eventService, EventNotificationService $notificationService)
{
    $reservation = $eventService->createReservation($event, $this->getUser());
    
    if ($reservation) {
        $notificationService->notifyReservationConfirmed($reservation);
        return new Response('Réservation confirmée!');
    }
}
```

## 🏗️ Architecture Découpée

La séparation des responsabilités permet:
- ✅ Réutilisation du code
- ✅ Testabilité améliorée
- ✅ Maintenabilité facilitée
- ✅ Scalabilité (chaque bundle peut évoluer indépendamment)
- ✅ Remplacement facile (ex: changer l'API sans affecter l'UI)

## 📦 Extensions Futures

Ces bundles peuvent être:
- Exportés en packages Composer standalone
- Versionnés séparément
- Testés indépendamment
- Réutilisés dans d'autres projets Symfony
