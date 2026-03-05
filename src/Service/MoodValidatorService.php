<?php

namespace App\Service;

use App\Entity\Mood;

class MoodValidatorService
{
    /**
     * Règle 1 : L'humeur est obligatoire.
     * Règle 2 : L'intensité doit être comprise entre 1 et 5.
     * Règle 3 : La date du mood ne peut pas être dans le futur.
     */
    public function validate(Mood $mood): bool
    {
        if (empty(trim($mood->getHumeur()))) {
            throw new \InvalidArgumentException('L\'humeur est obligatoire.');
        }

        if ($mood->getIntensite() < 1 || $mood->getIntensite() > 5) {
            throw new \InvalidArgumentException('L\'intensité doit être comprise entre 1 et 5.');
        }

        if ($mood->getDatemood() > new \DateTime('today')) {
            throw new \InvalidArgumentException('La date du mood ne peut pas être dans le futur.');
        }

        return true;
    }
}
