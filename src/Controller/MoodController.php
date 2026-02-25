<?php

namespace App\Controller;

use App\Entity\Mood;
use App\Form\MoodType;
use App\Message\AnalyzeAIMoodMessage;
use App\Message\GenerateMoodPdfMessage;
use App\Repository\MoodRepository;
use App\Repository\JournalEmotionnelRepository;
use App\Service\MeditationService;
use App\Service\AIJournalService;
use App\Service\PsychologicalAlertService;
use App\Service\StatsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use DateTime;
use DateInterval;

#[Route('/mood')]
final class MoodController extends AbstractController
{
    #[Route(name: 'app_mood_index', methods: ['GET'])]
    public function index(
        MoodRepository $moodRepository, 
        JournalEmotionnelRepository $journalRepository, 
        Request $request,
        ChartBuilderInterface $chartBuilder,
        PaginatorInterface $paginator,
        StatsService $statsService
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!$this->getUser() instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }
        $user = $this->getUser();
        
        $search = $request->query->get('search');
        $sort = $request->query->get('sort', 'datemood');
        $direction = $request->query->get('direction', 'desc');

        $qb = $moodRepository->createQueryBuilder('m');
        $qb->andWhere('m.user = :user')
            ->setParameter('user', $user);

        if ($search) {
            $qb->andWhere('m.humeur LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        // Valider les paramètres de tri
        $allowedSorts = ['id', 'humeur', 'intensite', 'datemood'];
        $allowedDirections = ['asc', 'desc'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'datemood';
        }
        
        if (!in_array($direction, $allowedDirections)) {
            $direction = 'desc';
        }
        
        $qb->orderBy('m.'.$sort, $direction);

        $allFilteredMoods = (clone $qb)->getQuery()->getResult();

        $moods = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            8
        );

        // Charger les données du journal émotionnel
        $journalEmotionnels = $journalRepository->findBy(['user' => $user]);

        // Préparer les données pour les graphiques
        $dates = [];
        $intensites = [];
        $humeurs = [];
        $moodTypes = ['heureux', 'neutre', 'triste', 'colere', 'stresse', 'calme', 'fatigue', 'excite'];
        $moodCounts = array_fill_keys($moodTypes, 0);

        foreach ($allFilteredMoods as $mood) {
            $dates[] = $mood->getDatemood() ? $mood->getDatemood()->format('Y-m-d') : '';
            $intensites[] = $mood->getIntensite();
            $humeurs[] = $mood->getHumeur();
            if (isset($moodCounts[$mood->getHumeur()])) {
                $moodCounts[$mood->getHumeur()]++;
            }
        }

        // Calculer les statistiques
        $stats = [
            'total' => count($allFilteredMoods),
            'moodCounts' => $moodCounts,
            'moodPercentages' => [],
            'averageIntensity' => 0,
            'maxIntensity' => 0,
            'minIntensity' => 0,
        ];

        if (!empty($allFilteredMoods)) {
            $totalIntensity = array_sum($intensites);
            $stats['averageIntensity'] = round($totalIntensity / count($allFilteredMoods), 1);
            $stats['maxIntensity'] = max($intensites);
            $stats['minIntensity'] = min($intensites);

            foreach ($moodCounts as $mood => $count) {
                $stats['moodPercentages'][$mood] = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
            }
        }

        $gamification = $statsService->getGamificationSummary($user);
        $points = $gamification['points'];
        $badges = $gamification['badges'] !== [] ? $gamification['badges'] : ['Aucun badge pour le moment'];

        // Fetch 'metiers avancée' data
        $metiersAvancee = [
            'title' => 'Amélioration des compétences',
            'description' => 'Des outils pour rendre votre travail plus avancé et efficace.',
            'tips' => [
                'Suivez vos humeurs quotidiennement.',
                'Analysez les pics d’intensité émotionnelle.',
                'Utilisez des techniques de relaxation pour équilibrer vos émotions.',
            ],
        ];

        // Create Mood Distribution Chart
        $moodDistributionChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $moodDistributionChart->setData([
            'labels' => ['😊 Heureux', '😐 Neutre', '😢 Triste', '😠 En Colère', '😰 Stressé', '😌 Calme', '😴 Fatigué', '😃 Excité'],
            'datasets' => [
                [
                    'label' => 'Distribution des Humeurs',
                    'data' => array_values($moodCounts),
                    'backgroundColor' => [
                        'rgba(72, 187, 120, 0.8)',
                        'rgba(160, 174, 192, 0.8)',
                        'rgba(247, 114, 155, 0.8)',
                        'rgba(245, 96, 82, 0.8)',
                        'rgba(237, 137, 54, 0.8)',
                        'rgba(129, 230, 217, 0.8)',
                        'rgba(246, 173, 85, 0.8)',
                        'rgba(99, 179, 237, 0.8)',
                    ],
                    'borderColor' => [
                        'rgb(72, 187, 120)',
                        'rgb(160, 174, 192)',
                        'rgb(247, 114, 155)',
                        'rgb(245, 96, 82)',
                        'rgb(237, 137, 54)',
                        'rgb(129, 230, 217)',
                        'rgb(246, 173, 85)',
                        'rgb(99, 179, 237)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
        ]);
        $moodDistributionChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['padding' => 15, 'font' => ['size' => 12]],
                ],
            ],
        ]);

        // Calculate intensity by mood
        $intensityByMood = [];
        $moodIntensityCount = array_fill_keys(array_keys($moodCounts), [0, 0]);
        
        foreach ($allFilteredMoods as $mood) {
            $moodType = $mood->getHumeur();
            if (isset($moodIntensityCount[$moodType])) {
                $moodIntensityCount[$moodType][0] += $mood->getIntensite();
                $moodIntensityCount[$moodType][1]++;
            }
        }
        
        foreach ($moodIntensityCount as $type => $data) {
            $intensityByMood[$type] = $data[1] > 0 ? round($data[0] / $data[1], 1) : 0;
        }

        $intensityChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $intensityChart->setData([
            'labels' => ['😊 Heureux', '😐 Neutre', '😢 Triste', '😠 En Colère', '😰 Stressé', '😌 Calme', '😴 Fatigué', '😃 Excité'],
            'datasets' => [
                [
                    'label' => 'Intensité Moyenne',
                    'data' => array_values($intensityByMood),
                    'backgroundColor' => 'rgba(126, 164, 255, 0.7)',
                    'borderColor' => 'rgb(126, 164, 255)',
                    'borderWidth' => 2,
                ],
            ],
        ]);
        $intensityChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => true,
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['beginAtZero' => true, 'max' => 10],
            ],
        ]);

        return $this->render('mood/index.html.twig', [
            'moods' => $moods,
            'stats' => $stats,
            'points' => $points,
            'badges' => $badges,
            'gamification' => $gamification,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'metiers_avancee' => $metiersAvancee,
            'moodDistributionChart' => $moodDistributionChart,
            'intensityChart' => $intensityChart,
        ]);
    }

    #[Route('/new', name: 'app_mood_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MessageBusInterface $bus, AIJournalService $aiService, PsychologicalAlertService $alertService, StatsService $statsService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $mood = new Mood();
        $mood->setUser($this->getUser());
        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($mood);
            $entityManager->flush();

            // Analyze AI immediately (synchronous)
            $analysis = $aiService->analyzJournal($mood->getHumeur());
            $analysisJson = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $mood->setAiAnalysis($analysisJson);
            
            $entityManager->persist($mood);
            $entityManager->flush();

            // Dispatch async message for PDF generation only
            $bus->dispatch(new GenerateMoodPdfMessage(
                $mood->getId(),
                $mood->getUser()->getId()
            ));

            // Alert logic:
            // - notify admin/psychologue on 3 consecutive negative moods
            // - show an encouragement message on 3 consecutive "heureux" moods
            try {
                $alertService->checkUserAlerts($mood->getUser());
                $positiveMessage = $alertService->getPositiveMomentumMessage($mood->getUser());
                if ($positiveMessage) {
                    $this->addFlash('mood_positive', $positiveMessage);
                }
                $statsService->recomputeStats($mood->getUser());
            } catch (\Throwable $e) {
                // Alert logic should never block mood creation.
                error_log('Mood alert check failed: ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_mood_new_success', ['id' => $mood->getId()]);
        }

        return $this->render('mood/new.html.twig', [
            'mood' => $mood,
            'form' => $form,
            'stats' => [
                'total' => 0,
                'moodCounts' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'moodPercentages' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'averageIntensity' => 0,
            ],
        ]);
    }

    #[Route('/new/success/{id}', name: 'app_mood_new_success', methods: ['GET'])]
    public function newSuccess(Mood $mood): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $aiAnalysis = null;
        if ($mood->getAiAnalysis()) {
            $aiAnalysis = json_decode($mood->getAiAnalysis(), true);
        }

        return $this->render('mood/new_success.html.twig', [
            'mood' => $mood,
            'ai_analysis' => $aiAnalysis,
            'stats' => [
                'total' => 0,
                'moodCounts' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'moodPercentages' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'averageIntensity' => 0,
            ],
        ]);
    }

    #[Route('/heatmap', name: 'app_mood_heatmap', methods: ['GET'])]
    public function heatmap(MoodRepository $moodRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        $year = (int) $request->query->get('year', date('Y'));
        $month = (int) $request->query->get('month', date('m'));
        
        // Validate month and year
        if ($month < 1 || $month > 12) {
            $month = (int) date('m');
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        
        // Get all moods for the user in the selected month
        $startDate = new DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $endDate->add(new DateInterval('P1M'))->sub(new DateInterval('P1D'));
        
        $moods = $moodRepository->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.datemood >= :start AND m.datemood <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('m.datemood', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Build heatmap data by day
        $heatmapData = [];
        for ($day = 1; $day <= $endDate->format('d'); $day++) {
            $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $heatmapData[$dateKey] = null;
        }
        
        // Map moods to dates
        foreach ($moods as $mood) {
            $dateKey = $mood->getDatemood()->format('Y-m-d');
            if (isset($heatmapData[$dateKey])) {
                // Keep the last mood of the day
                $heatmapData[$dateKey] = [
                    'humeur' => $mood->getHumeur(),
                    'intensite' => $mood->getIntensite(),
                    'id' => $mood->getId(),
                ];
            } else {
                $heatmapData[$dateKey] = [
                    'humeur' => $mood->getHumeur(),
                    'intensite' => $mood->getIntensite(),
                    'id' => $mood->getId(),
                ];
            }
        }
        
        // Get emotion colors
        $emotionColors = [
            'heureux' => '#2ecc71',
            'triste' => '#3498db',
            'colere' => '#c0392b',
            'stresse' => '#e74c3c',
            'calme' => '#9b59b6',
            'fatigue' => '#f39c12',
            'excite' => '#e67e22',
            'neutre' => '#95a5a6'
        ];
        
        // Navigation months
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        
        return $this->render('mood/heatmap.html.twig', [
            'heatmapData' => $heatmapData,
            'year' => $year,
            'month' => $month,
            'daysInMonth' => (int)$endDate->format('d'),
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'nextYear' => $nextYear,
            'nextMonth' => $nextMonth,
            'emotionColors' => $emotionColors,
        ]);
    }

    #[Route('/{id}', name: 'app_mood_show', methods: ['GET'])]
    public function show(Mood $mood, MeditationService $meditationService): Response
    {
        // Vérifier que le mood appartient à l'utilisateur connecté
        if ($mood->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder au mood d\'un autre utilisateur.');
        }
        
        $meditations = $meditationService->getMeditationsByMood($mood->getHumeur());
        $affirmations = $meditationService->getAffirmations();
        $breathingExercises = $meditationService->getBreathingExercises();
        
        // Decode AI analysis JSON if available
        $aiAnalysis = null;
        if ($mood->getAiAnalysis()) {
            $aiAnalysis = json_decode($mood->getAiAnalysis(), true);
        }

        return $this->render('mood/show.html.twig', [
            'mood' => $mood,
            'meditations' => $meditations,
            'affirmations' => $affirmations,
            'breathing_exercises' => $breathingExercises,
            'ai_analysis' => $aiAnalysis,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mood_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mood $mood, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le mood appartient à l'utilisateur connecté
        if ($mood->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas éditer le mood d\'un autre utilisateur.');
        }
        
        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mood/edit.html.twig', [
            'mood' => $mood,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/confirm-delete', name: 'app_mood_confirm_delete', methods: ['GET'])]
    public function confirmDelete(Mood $mood): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($mood->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer le mood d\'un autre utilisateur.');
        }

        return $this->render('mood/confirm_delete.html.twig', [
            'mood' => $mood,
        ]);
    }

    #[Route('/{id}', name: 'app_mood_delete', methods: ['POST'])]
    public function delete(Request $request, Mood $mood, EntityManagerInterface $entityManager, MoodRepository $moodRepository): Response
    {
        // Vérifier que l'utilisateur est authentifié
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Vérifier que le mood appartient à l'utilisateur connecté
        if ($mood->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer le mood d\'un autre utilisateur.');
        }
        
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!is_string($token) || $token === '' || !$this->isCsrfTokenValid('delete'.$mood->getId(), $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($request->request->get('confirm_delete') !== 'yes') {
            $this->addFlash('error', 'Veuillez confirmer la suppression.');
            return $this->redirectToRoute('app_mood_confirm_delete', ['id' => $mood->getId()]);
        }
        
        $moodId = $mood->getId();
        $maxRetries = 3;
        $attempt = 0;
        $lastException = null;
        
        // Retry mechanism for SQLite locking issues
        while ($attempt < $maxRetries) {
            try {
                $moodRepository->createQueryBuilder('m')
                    ->delete()
                    ->where('m.id = :id')
                    ->setParameter('id', $moodId)
                    ->getQuery()
                    ->execute();
                
                return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                
                // If database is locked, wait and retry
                if (strpos($e->getMessage(), 'database is locked') !== false && $attempt < $maxRetries) {
                    usleep(100000); // Wait 100ms before retry
                    $entityManager->getConnection()->close();
                    continue;
                }
                
                // For other errors, throw immediately
                throw $e;
            }
        }
        
        // If all retries failed
        throw $lastException ?? new \RuntimeException('Failed to delete mood after ' . $maxRetries . ' attempts');
    }

    #[Route('/{id}/download-pdf', name: 'app_mood_download_pdf', methods: ['GET'])]
    public function downloadPdf(Mood $mood): Response
    {
        // Vérifier que l'utilisateur est authentifié et propriétaire
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if ($mood->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas télécharger le PDF d\'un autre utilisateur.');
        }
        
        // Vérifier que le PDF existe
        if (!$mood->getPdfPath()) {
            $this->addFlash('warning', 'Le PDF pour cette humeur n\'a pas encore été généré. Veuillez réessayer dans quelques secondes.');
            return $this->redirectToRoute('app_mood_show', ['id' => $mood->getId()]);
        }
        
        $pdfPath = $this->getParameter('kernel.project_dir') . '/public/uploads/moods_pdf/' . $mood->getPdfPath();
        
        // Vérifier que le fichier existe
        if (!file_exists($pdfPath)) {
            $this->addFlash('error', 'Le fichier PDF n\'existe pas.');
            return $this->redirectToRoute('app_mood_show', ['id' => $mood->getId()]);
        }
        
        // Retourner le fichier PDF
        return $this->file($pdfPath, $mood->getPdfPath(), 'inline');
    }
}

