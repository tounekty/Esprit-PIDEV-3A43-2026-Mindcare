<?php
namespace App\Service;

use App\Repository\MoodRepository;

class MoodSearchService
{
    public function __construct(private MoodRepository $moodRepository) {}

    public function rechercherParCritere(
        int $userId,
        ?string $humeur,
        ?int $intensite,
        ?\DateTimeInterface $date
    ) {
        return $this->moodRepository->searchMood(
            $userId,
            $humeur,
            $intensite,
            $date
        );
    }
}
