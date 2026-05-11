<?php

namespace App\Service;

use App\Entity\Event;

class EventValidatorService
{
    /**
     * Règle 1 : Le titre est obligatoire.
     * Règle 2 : La capacité doit être strictement positive.
     * Règle 3 : La date de l'événement doit être dans le futur.
     */
    public function validate(Event $event): bool
    {
        if (empty(trim($event->getTitre()))) {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }

        if ($event->getCapacite() <= 0) {
            throw new \InvalidArgumentException('La capacité doit être strictement positive.');
        }

        if ($event->getDateEvent() <= new \DateTime('now')) {
            throw new \InvalidArgumentException('La date de l\'événement doit être dans le futur.');
        }

        return true;
    }
}
