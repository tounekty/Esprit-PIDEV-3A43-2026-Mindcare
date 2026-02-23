<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Mood;
use App\Entity\JournalEmotionnel;
use App\Form\MoodType;
use App\Form\JournalEmotionnelType;
use App\Repository\JournalEmotionnelRepository;
use App\Repository\MoodRepository;
use App\Service\MeditationService;
use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin', methods: ['GET'])]
    public function index(StatisticsService $statisticsService): Response
    {
        $statistics = $statisticsService->getUserStatistics();
        $chartData = $statisticsService->getChartData();
        
        return $this->render('admin/index.html.twig', [
            'statistics' => $statistics,
            'chartData' => json_encode($chartData),
        ]);
    }

    #[Route('/moods', name: 'admin_moods', methods: ['GET'])]
    public function moods(MoodRepository $moodRepository): Response
    {
        return $this->render('admin/moods.html.twig', [
            'moods' => $moodRepository->findBy([], ['datemood' => 'DESC']),
        ]);
    }

    #[Route('/journals', name: 'admin_journals', methods: ['GET'])]
    public function journals(JournalEmotionnelRepository $journalRepository): Response
    {
        return $this->render('admin/journals.html.twig', [
            'journals' => $journalRepository->findBy([], ['dateecriture' => 'DESC']),
        ]);
    }

    #[Route('/meditations', name: 'admin_meditations', methods: ['GET'])]
    public function meditations(MeditationService $meditationService): Response
    {
        return $this->render('admin/meditations.html.twig', [
            'meditations_by_mood' => [
                'heureux' => $meditationService->getMeditationsByMood('heureux'),
                'triste' => $meditationService->getMeditationsByMood('triste'),
                'colere' => $meditationService->getMeditationsByMood('colere'),
                'stresse' => $meditationService->getMeditationsByMood('stresse'),
                'neutre' => $meditationService->getMeditationsByMood('neutre'),
            ],
            'affirmations' => $meditationService->getAffirmations(),
            'breathing_exercises' => $meditationService->getBreathingExercises(),
        ]);
    }
}