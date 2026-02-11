<?php

namespace App\Controller;

use App\Repository\MoodRepository;
use App\Repository\JournalEmotionnelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard')]
final class DashboardController extends AbstractController
{
    #[Route('/breathing', name: 'app_dashboard_breathing', methods: ['GET'])]
    public function breathing(): Response
    {
        return $this->render('dashboard/breathing.html.twig');
    }

    #[Route('/sleep', name: 'app_dashboard_sleep', methods: ['GET'])]
    public function sleep(): Response
    {
        return $this->render('dashboard/sleep.html.twig');
    }
    #[Route(name: 'app_dashboard', methods: ['GET'])]
    public function index(MoodRepository $moodRepository, JournalEmotionnelRepository $journalRepository): Response
    {
        $user = $this->getUser();
        $recentMoods = $moodRepository->findBy([], ['datemood' => 'DESC'], 5);
        $recentJournals = $journalRepository->findBy([], ['dateecriture' => 'DESC'], 3);

        // Calculate mood statistics
        $moods = $moodRepository->findAll();
        $moodStats = [
            'heureux' => 0,
            'triste' => 0,
            'colere' => 0,
            'stresse' => 0,
            'neutre' => 0
        ];

        foreach ($moods as $mood) {
            if (isset($moodStats[$mood->getHumeur()])) {
                $moodStats[$mood->getHumeur()]++;
            }
        }

        // Prepare data for charts
        $moodLabels = array_keys($moodStats);
        $moodCounts = array_values($moodStats);

        // Breathing exercises resources
        $breathingExercises = [
            [
                'title' => 'Respiration carrée',
                'description' => "Inspirez 4 secondes, retenez 4 secondes, expirez 4 secondes, retenez 4 secondes.",
                'link' => '#',
            ],
            [
                'title' => 'Cohérence cardiaque',
                'description' => "Inspirez 5 secondes, expirez 5 secondes, pendant 5 minutes.",
                'link' => '#',
            ],
            [
                'title' => 'Respiration profonde',
                'description' => "Inspirez lentement par le nez, expirez doucement par la bouche.",
                'link' => '#',
            ],
        ];

        return $this->render('dashboard/index.html.twig', [
            'recentMoods' => $recentMoods,
            'recentJournals' => $recentJournals,
            'moodStats' => $moodStats,
            'moodLabels' => json_encode($moodLabels),
            'moodCounts' => json_encode($moodCounts),
            'breathingExercises' => $breathingExercises,
        ]);
    }
}