<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserStats;
use App\Repository\UserStatsRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatsService
{
    public function __construct(
        private UserStatsRepository $userStatsRepository,
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
            $this->entityManager->persist($stats);
            $this->entityManager->flush();
        }
        
        return $stats;
    }

    public function addEntryPoints(User $user, int $basePoints = 10): UserStats
    {
        $stats = $this->getOrCreateStats($user);
        $stats->addPoints($basePoints);
        
        // Check badges
        $this->checkBadges($stats);
        
        $this->entityManager->flush();
        return $stats;
    }

    public function updateConsecutiveDays(User $user): UserStats
    {
        $stats = $this->getOrCreateStats($user);
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        if ($stats->getLastEntryDate()) {
            $lastDate = clone $stats->getLastEntryDate();
            $lastDate->setTime(0, 0, 0);
            
            $diff = $today->diff($lastDate)->days;
            
            if ($diff === 1) {
                // Consecutive day
                $stats->setConsecutiveDays($stats->getConsecutiveDays() + 1);
                $stats->addPoints(5); // Bonus for consecutive days
            } elseif ($diff > 1) {
                // Reset streak
                $stats->setConsecutiveDays(1);
            }
        } else {
            $stats->setConsecutiveDays(1);
        }
        
        $stats->setLastEntryDate($today);
        $this->checkBadges($stats);
        
        $this->entityManager->flush();
        return $stats;
    }

    public function incrementEntryCount(User $user): UserStats
    {
        $stats = $this->getOrCreateStats($user);
        $stats->setTotalEntries($stats->getTotalEntries() + 1);
        
        $this->checkBadges($stats);
        
        $this->entityManager->flush();
        return $stats;
    }

    private function checkBadges(UserStats $stats): void
    {
        // 7 days badge
        if ($stats->getConsecutiveDays() >= 7 && !$stats->hasBadge('seven_days')) {
            $stats->addBadge('seven_days');
            $stats->addPoints(50);
        }

        // 30 days badge
        if ($stats->getConsecutiveDays() >= 30 && !$stats->hasBadge('thirty_days')) {
            $stats->addBadge('thirty_days');
            $stats->addPoints(100);
        }

        // 100 entries badge
        if ($stats->getTotalEntries() >= 100 && !$stats->hasBadge('100_entries')) {
            $stats->addBadge('100_entries');
            $stats->addPoints(75);
        }

        // 50 entries badge
        if ($stats->getTotalEntries() >= 50 && !$stats->hasBadge('50_entries')) {
            $stats->addBadge('50_entries');
            $stats->addPoints(50);
        }

        // 10 entries badge
        if ($stats->getTotalEntries() >= 10 && !$stats->hasBadge('10_entries')) {
            $stats->addBadge('10_entries');
            $stats->addPoints(25);
        }
    }

    public function getBadgeInfo(string $badge): array
    {
        $badgeData = [
            'seven_days' => [
                'name' => '7 Jours d\'Affilée',
                'icon' => '🔥',
                'description' => 'Écrivez pendant 7 jours consécutifs'
            ],
            'thirty_days' => [
                'name' => '30 Jours d\'Affilée',
                'icon' => '🏆',
                'description' => 'Écrivez pendant 30 jours consécutifs'
            ],
            '10_entries' => [
                'name' => '10 Entrées',
                'icon' => '📝',
                'description' => 'Créez 10 entrées de journal'
            ],
            '50_entries' => [
                'name' => '50 Entrées',
                'icon' => '⭐',
                'description' => 'Créez 50 entrées de journal'
            ],
            '100_entries' => [
                'name' => '100 Entrées',
                'icon' => '💎',
                'description' => 'Créez 100 entrées de journal'
            ],
        ];
        
        return $badgeData[$badge] ?? [];
    }
}
