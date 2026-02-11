<?php
namespace App\Service;

class MoodAnalyticsService
{
    public function trierParDate(array $moods): array
    {
        usort($moods, fn($a, $b) =>
            $a->getDateMood() <=> $b->getDateMood()
        );
        return $moods;
    }

    public function calculerMoyenne(array $moods): float
    {
        if (count($moods) === 0) return 0;

        $total = array_sum(array_map(
            fn($m) => $m->getIntensite(),
            $moods
        ));

        return $total / count($moods);
    }

    public function detecterPeriodeCritique(array $moods): bool
    {
        $lowCount = 0;
        foreach ($moods as $mood) {
            if ($mood->getIntensite() <= 2) {
                $lowCount++;
            }
        }
        return $lowCount >= 3;
    }
}
