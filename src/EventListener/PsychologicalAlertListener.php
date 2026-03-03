<?php

namespace App\EventListener;

use App\Entity\Mood;
use App\Entity\Commentaire;
use App\Service\PsychologicalAlertService;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class PsychologicalAlertListener
{
    public function __construct(private PsychologicalAlertService $alertService) {}

    // Listener disabled for now - use manual testing instead
    // Uncomment these methods to enable automatic alert checking

    /*
    public function postPersist(PostPersistEventArgs $args): void
    {
        try {
            $entity = $args->getObject();

            if ($entity instanceof Mood && $entity->getUser()) {
                $this->alertService->checkUserAlerts($entity->getUser());
            }

            if ($entity instanceof Commentaire && $entity->getUser()) {
                $this->alertService->checkUserAlerts($entity->getUser());
            }
        } catch (\Exception $e) {
            error_log('Error checking psychological alerts: ' . $e->getMessage());
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        try {
            $entity = $args->getObject();

            if ($entity instanceof Mood && $entity->getUser()) {
                $this->alertService->checkUserAlerts($entity->getUser());
            }

            if ($entity instanceof Commentaire && $entity->getUser()) {
                $this->alertService->checkUserAlerts($entity->getUser());
            }
        } catch (\Exception $e) {
            error_log('Error checking psychological alerts: ' . $e->getMessage());
        }
    }
    */
}
