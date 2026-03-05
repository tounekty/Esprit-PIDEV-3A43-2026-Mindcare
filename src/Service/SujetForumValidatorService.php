<?php

namespace App\Service;

use App\Entity\SujetForum;

class SujetForumValidatorService
{
    /**
     * Règle 1 : Le titre est obligatoire et doit avoir au moins 5 caractères.
     * Règle 2 : La description est obligatoire et doit avoir au moins 10 caractères.
     * Règle 3 : Le statut doit être parmi les valeurs autorisées.
     */
    public function validate(SujetForum $sujet): bool
    {
        if (strlen(trim($sujet->getTitre())) < 5) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 5 caractères.');
        }

        if (strlen(trim($sujet->getDescription())) < 10) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères.');
        }

        $status = $sujet->getStatus();
        if ($status !== null && !in_array($status, SujetForum::getStatusValues(), true)) {
            throw new \InvalidArgumentException('Le statut du sujet est invalide.');
        }

        return true;
    }
}
