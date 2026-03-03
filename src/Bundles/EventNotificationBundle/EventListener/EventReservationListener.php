<?php

namespace App\Bundles\EventNotificationBundle\EventListener;

use App\Bundles\EventNotificationBundle\Service\EventNotificationService;
use App\Entity\EventReservation;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Event Listener pour les notifications d'événements
 */
#[AsEntityListener(event: Events::postPersist, entity: EventReservation::class)]
#[AsEntityListener(event: Events::postUpdate, entity: EventReservation::class)]
class EventReservationListener
{
    public function __construct(
        private EventNotificationService $notificationService
    ) {}

    public function postPersist(EventReservation $entity, PostPersistEventArgs $args): void
    {
        // Envoie une notification quand une nouvelle réservation est créée
        if ($entity->getStatut() === EventReservation::STATUS_PENDING) {
            $this->notificationService->notifyReservationConfirmed($entity);
        }
    }

    public function postUpdate(EventReservation $entity, PostUpdateEventArgs $args): void
    {
        // Envoie une notification quand une réservation est annulée
        if ($entity->getStatut() === EventReservation::STATUS_CANCELLED) {
            $this->notificationService->notifyReservationCancelled($entity);
        }
    }
}
