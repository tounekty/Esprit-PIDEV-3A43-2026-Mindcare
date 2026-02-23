<?php

namespace App\Controller;

use App\Repository\EntryTemplateRepository;
use App\Repository\UserStatsRepository;
use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
final class DashboardStatsController extends AbstractController
{
    #[Route('', name: 'app_dashboard_stats', methods: ['GET'])]
    public function stats(UserStatsRepository $userStatsRepository, StatsService $statsService, EntryTemplateRepository $templateRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        $userStats = $statsService->getOrCreateStats($user);
        $templates = $templateRepository->findActiveTemplates();
        
        // Calculate badge information
        $userBadges = [];
        foreach ($userStats->getBadges() as $badge) {
            $userBadges[$badge] = $statsService->getBadgeInfo($badge);
        }
        
        // All available badges for comparison
        $allBadges = [
            '10_entries' => $statsService->getBadgeInfo('10_entries'),
            '50_entries' => $statsService->getBadgeInfo('50_entries'),
            '100_entries' => $statsService->getBadgeInfo('100_entries'),
            'seven_days' => $statsService->getBadgeInfo('seven_days'),
            'thirty_days' => $statsService->getBadgeInfo('thirty_days'),
        ];
        
        // Calculate next badge progress
        $nextBadge = null;
        $nextBadgeProgress = 0;
        
        if ($userStats->getTotalEntries() < 10) {
            $nextBadge = 'entries';
            $nextBadgeProgress = round(($userStats->getTotalEntries() / 10) * 100);
        } elseif ($userStats->getTotalEntries() < 50) {
            $nextBadge = 'entries50';
            $nextBadgeProgress = round(($userStats->getTotalEntries() / 50) * 100);
        } elseif ($userStats->getTotalEntries() < 100) {
            $nextBadge = 'entries100';
            $nextBadgeProgress = round(($userStats->getTotalEntries() / 100) * 100);
        }
        
        if ($userStats->getConsecutiveDays() < 7 && !in_array('seven_days', $userStats->getBadges())) {
            $nextBadge = 'days7';
            $nextBadgeProgress = round(($userStats->getConsecutiveDays() / 7) * 100);
        }

        return $this->render('dashboard/stats.html.twig', [
            'user_stats' => $userStats,
            'user_badges' => $userBadges,
            'all_badges' => $allBadges,
            'templates' => $templates,
            'next_badge' => $nextBadge,
            'next_badge_progress' => $nextBadgeProgress,
        ]);
    }
}
