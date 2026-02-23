<?php
// src/Service/MoodApiService.php
namespace App\Service;

class MoodApiService
{
    public function getRandomMood(): array
    {
        $humeurs = ['heureux', 'triste', 'neutre', 'stressé', 'excité'];
        $intensite = rand(1, 5);
        $humeur = $humeurs[array_rand($humeurs)];

        return [
            'humeur' => $humeur,
            'intensite' => $intensite,
        ];
    }
}
