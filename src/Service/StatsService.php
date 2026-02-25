<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserStats;
use App\Repository\JournalEmotionnelRepository;
use App\Repository\MoodRepository;
use App\Repository\UserStatsRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatsService
{
    public function __construct(
        private UserStatsRepository $userStatsRepository,
        private MoodRepository $moodRepository,
        private JournalEmotionnelRepository $journalRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function getOrCreateStats(User $user): UserStats
    {
        $stats = $this->userStatsRepository->findByUser($user);

        if (!$stats) {
            $stats = new UserStats();
            $stats->setUser($user);
            $stats->setPoints(0);
            $stats->setBadges([]);
            $stats->setTotalEntries(0);
            $stats->setConsecutiveDays(0);
            $this->entityManager->persist($stats);
            $this->entityManager->flush();
        }

        return $stats;
    }

    public function recomputeStats(User $user): UserStats
    {
        $stats = $this->getOrCreateStats($user);

        $moodCount = $this->moodRepository->count(['user' => $user]);
        $journalCount = $this->journalRepository->count(['user' => $user]);
        $totalEntries = $moodCount + $journalCount;

        $activityDates = $this->getActivityDates($user);
        $currentStreak = $this->computeCurrentStreak($activityDates);
        $lastEntryDate = $activityDates !== [] ? new \DateTime(reset($activityDates)) : null;
        $distinctMoodCount = $this->getDistinctMoodCount($user);

        $badges = $this->resolveBadges($totalEntries, $journalCount, $currentStreak, $distinctMoodCount);
        $points = $this->computePoints($totalEntries, $journalCount, $currentStreak, $distinctMoodCount, $badges);

        $stats
            ->setTotalEntries($totalEntries)
            ->setConsecutiveDays($currentStreak)
            ->setLastEntryDate($lastEntryDate)
            ->setBadges($badges)
            ->setPoints($points);

        $this->entityManager->flush();

        return $stats;
    }

    public function getGamificationSummary(User $user): array
    {
        $stats = $this->recomputeStats($user);

        return [
            'points' => $stats->getPoints(),
            'badges' => $this->getBadgeDisplayNames($stats->getBadges()),
            'streak' => $stats->getConsecutiveDays(),
            'entries' => $stats->getTotalEntries(),
        ];
    }

    public function addEntryPoints(User $user, int $basePoints = 10): UserStats
    {
        return $this->recomputeStats($user);
    }

    public function updateConsecutiveDays(User $user): UserStats
    {
        return $this->recomputeStats($user);
    }

    public function incrementEntryCount(User $user): UserStats
    {
        return $this->recomputeStats($user);
    }

    public function getBadgeInfo(string $badge): array
    {
        $badgeData = [
            'first_entry' => [
                'name' => 'Premier Pas',
                'icon' => '🌱',
                'description' => 'Créer votre première entrée mood/journal',
            ],
            'ten_entries' => [
                'name' => 'Régularité 10',
                'icon' => '📝',
                'description' => 'Atteindre 10 entrées au total',
            ],
            'thirty_entries' => [
                'name' => 'Régularité 30',
                'icon' => '⭐',
                'description' => 'Atteindre 30 entrées au total',
            ],
            'hundred_entries' => [
                'name' => 'Centurion',
                'icon' => '💎',
                'description' => 'Atteindre 100 entrées au total',
            ],
            'streak_3' => [
                'name' => 'Série 3 jours',
                'icon' => '🔥',
                'description' => '3 jours consécutifs d’activité',
            ],
            'streak_7' => [
                'name' => 'Série 7 jours',
                'icon' => '🏅',
                'description' => '7 jours consécutifs d’activité',
            ],
            'streak_30' => [
                'name' => 'Série 30 jours',
                'icon' => '🏆',
                'description' => '30 jours consécutifs d’activité',
            ],
            'mood_explorer' => [
                'name' => 'Explorateur Émotionnel',
                'icon' => '🧭',
                'description' => 'Utiliser au moins 4 humeurs différentes',
            ],
            'mood_master' => [
                'name' => 'Maître des Humeurs',
                'icon' => '🎭',
                'description' => 'Utiliser les 8 humeurs disponibles',
            ],
            'journal_writer' => [
                'name' => 'Plume Active',
                'icon' => '✍️',
                'description' => 'Écrire 10 journaux',
            ],
            'journal_pro' => [
                'name' => 'Chroniqueur',
                'icon' => '📚',
                'description' => 'Écrire 30 journaux',
            ],
            // Legacy keys kept for dashboard compatibility
            'seven_days' => [
                'name' => '7 Jours d’Affilée',
                'icon' => '🔥',
                'description' => '7 jours consécutifs d’activité',
            ],
            'thirty_days' => [
                'name' => '30 Jours d’Affilée',
                'icon' => '🏆',
                'description' => '30 jours consécutifs d’activité',
            ],
            '10_entries' => [
                'name' => '10 Entrées',
                'icon' => '📝',
                'description' => 'Créer 10 entrées',
            ],
            '50_entries' => [
                'name' => '50 Entrées',
                'icon' => '⭐',
                'description' => 'Créer 50 entrées',
            ],
            '100_entries' => [
                'name' => '100 Entrées',
                'icon' => '💎',
                'description' => 'Créer 100 entrées',
            ],
        ];

        return $badgeData[$badge] ?? ['name' => $badge, 'icon' => '🏷️', 'description' => 'Badge débloqué'];
    }

    private function getBadgeDisplayNames(array $badgeKeys): array
    {
        $labels = [];

        foreach ($badgeKeys as $badge) {
            $info = $this->getBadgeInfo($badge);
            $labels[] = ($info['icon'] ?? '🏷️') . ' ' . ($info['name'] ?? $badge);
        }

        return $labels;
    }

    private function getDistinctMoodCount(User $user): int
    {
        $rows = $this->moodRepository->createQueryBuilder('m')
            ->select('DISTINCT m.humeur AS humeur')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return count($rows);
    }

    /**
     * @return list<string> Dates in Y-m-d format sorted descending
     */
    private function getActivityDates(User $user): array
    {
        $dates = [];

        $moodRows = $this->moodRepository->createQueryBuilder('m')
            ->select('m.datemood AS d')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.datemood', 'DESC')
            ->getQuery()
            ->getArrayResult();

        foreach ($moodRows as $row) {
            if (($row['d'] ?? null) instanceof \DateTimeInterface) {
                $dates[$row['d']->format('Y-m-d')] = true;
            } elseif (is_string($row['d'] ?? null)) {
                $dates[(new \DateTime($row['d']))->format('Y-m-d')] = true;
            }
        }

        $journalRows = $this->journalRepository->createQueryBuilder('j')
            ->select('j.dateecriture AS d')
            ->andWhere('j.user = :user')
            ->setParameter('user', $user)
            ->orderBy('j.dateecriture', 'DESC')
            ->getQuery()
            ->getArrayResult();

        foreach ($journalRows as $row) {
            if (($row['d'] ?? null) instanceof \DateTimeInterface) {
                $dates[$row['d']->format('Y-m-d')] = true;
            } elseif (is_string($row['d'] ?? null)) {
                $dates[(new \DateTime($row['d']))->format('Y-m-d')] = true;
            }
        }

        $dateList = array_keys($dates);
        rsort($dateList);

        return $dateList;
    }

    /**
     * @param list<string> $activityDates Y-m-d sorted descending
     */
    private function computeCurrentStreak(array $activityDates): int
    {
        if ($activityDates === []) {
            return 0;
        }

        $today = new \DateTimeImmutable('today');
        $latest = new \DateTimeImmutable($activityDates[0]);
        $gapFromToday = (int) $today->diff($latest)->format('%r%a');

        // If last activity is older than yesterday, the active streak is broken.
        if ($gapFromToday > 1) {
            return 0;
        }

        $streak = 1;
        $cursor = $latest;

        for ($i = 1; $i < count($activityDates); $i++) {
            $next = new \DateTimeImmutable($activityDates[$i]);
            if ($cursor->diff($next)->days === 1) {
                $streak++;
                $cursor = $next;
                continue;
            }

            break;
        }

        return $streak;
    }

    /**
     * @return list<string>
     */
    private function resolveBadges(int $totalEntries, int $journalCount, int $streak, int $distinctMoodCount): array
    {
        $badges = [];

        if ($totalEntries >= 1) {
            $badges[] = 'first_entry';
        }
        if ($totalEntries >= 10) {
            $badges[] = 'ten_entries';
        }
        if ($totalEntries >= 30) {
            $badges[] = 'thirty_entries';
        }
        if ($totalEntries >= 100) {
            $badges[] = 'hundred_entries';
        }

        if ($streak >= 3) {
            $badges[] = 'streak_3';
        }
        if ($streak >= 7) {
            $badges[] = 'streak_7';
        }
        if ($streak >= 30) {
            $badges[] = 'streak_30';
        }

        if ($distinctMoodCount >= 4) {
            $badges[] = 'mood_explorer';
        }
        if ($distinctMoodCount >= 8) {
            $badges[] = 'mood_master';
        }

        if ($journalCount >= 10) {
            $badges[] = 'journal_writer';
        }
        if ($journalCount >= 30) {
            $badges[] = 'journal_pro';
        }

        return array_values(array_unique($badges));
    }

    /**
     * @param list<string> $badges
     */
    private function computePoints(int $totalEntries, int $journalCount, int $streak, int $distinctMoodCount, array $badges): int
    {
        // Base progression + consistency + diversity + writing depth.
        $points = ($totalEntries * 10)
            + ($streak * 5)
            + ($distinctMoodCount * 8)
            + ($journalCount * 2)
            + (count($badges) * 20);

        return max(0, $points);
    }
}
