<?php

namespace App\Bundles\EventNotificationBundle\EventListener;

use App\Bundles\EventCoreBundle\Service\CapacityManager;
use App\Bundles\EventNotificationBundle\Service\EventNotificationService;
use App\Entity\EventReservation;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Listener pour gérer les promotions automatiques depuis la liste d'attente
 * 
 * Quand une réservation est annulée ou refusée:
 * - Si elle était confirmée → le premier en attente est promu
 */
#[AsEntityListener(event: Events::postUpdate, entity: EventReservation::class)]
class WaitingListPromotionListener
{
    public function __construct(
        private CapacityManager $capacityManager,
        private EventNotificationService $notificationService
    ) {}

    public function postUpdate(EventReservation $entity, PostUpdateEventArgs $args): void
    {
        // Waiting list feature has been removed — this listener is now a no-op
        return;
    }

    /**
     * Promeut le premier en attente pour cet événement
     */
    private function promoteFirstWaiting(EventReservation $cancelledReservation): void
    {
        $event = $cancelledReservation->getEvent();

        // Récupérer le premier en attente
        $waitingReservations = $this->capacityManager->getWaitingReservations($event);

        if (!empty($waitingReservations)) {
            $firstWaiting = $waitingReservations[0];

            // Promouvoir
            $this->capacityManager->promoteFromWaiting($firstWaiting);

            // Notifier de la promotion
            $this->notificationService->notifyPromotedFromWaiting($firstWaiting);
        }
    }
}
