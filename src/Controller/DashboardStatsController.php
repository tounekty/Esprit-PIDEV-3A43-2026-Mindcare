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
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }

        $userStats = $statsService->recomputeStats($user);
        $templates = $templateRepository->findActiveTemplates();
        
        // Calculate badge information
        $userBadges = [];
        foreach ($userStats->getBadges() as $badge) {
            $userBadges[$badge] = $statsService->getBadgeInfo($badge);
        }
        
        // All available badges for comparison
        $allBadges = [
            'first_entry' => $statsService->getBadgeInfo('first_entry'),
            'ten_entries' => $statsService->getBadgeInfo('ten_entries'),
            'thirty_entries' => $statsService->getBadgeInfo('thirty_entries'),
            'hundred_entries' => $statsService->getBadgeInfo('hundred_entries'),
            'streak_3' => $statsService->getBadgeInfo('streak_3'),
            'streak_7' => $statsService->getBadgeInfo('streak_7'),
            'streak_30' => $statsService->getBadgeInfo('streak_30'),
            'mood_explorer' => $statsService->getBadgeInfo('mood_explorer'),
            'mood_master' => $statsService->getBadgeInfo('mood_master'),
            'journal_writer' => $statsService->getBadgeInfo('journal_writer'),
            'journal_pro' => $statsService->getBadgeInfo('journal_pro'),
        ];
        
        // Calculate next badge progress
        $nextBadge = null;
        $nextBadgeProgress = 0;
        
        if ($userStats->getTotalEntries() < 10) {
            $nextBadge = 'entries';
            $nextBadgeProgress = round(($userStats->getTotalEntries() / 10) * 100);
        } elseif ($userStats->getTotalEntries() < 30) {
            $nextBadge = 'entries30';
            $nextBadgeProgress = round(($userStats->getTotalEntries() / 30) * 100);
        } elseif ($userStats->getTotalEntries() < 100) {
            $nextBadge = 'entries100';
            $nextBadgeProgress = round(($userStats->getTotalEntries() / 100) * 100);
        }
        
        if ($userStats->getConsecutiveDays() < 7 && !in_array('streak_7', $userStats->getBadges(), true)) {
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
