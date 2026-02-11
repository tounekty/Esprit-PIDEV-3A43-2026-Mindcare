<?php

namespace App\Service;

class MoodDecisionService
{
    /**
     * Décision automatique selon l’intensité
     */
    public function recommendAction(int $intensite): string
    {
        return match (true) {
            $intensite <= 2 => 'Contacter le psychologue',
            $intensite === 3 => 'Exercices de respiration',
            default => 'Continuez votre suivi 👍',
        };
    }
}
