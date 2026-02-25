<?php

namespace App\Controller;

use App\Entity\JournalEmotionnel;
use App\Form\JournalEmotionnelType;
use App\Message\GenerateJournalPdfMessage;
use App\Repository\JournalEmotionnelRepository;
use App\Repository\MoodRepository;
use App\Service\MeditationService;
use App\Service\MoodProviderService;
use App\Service\StatsService;
use App\Service\AIJournalService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/journal/emotionnel')]
final class JournalEmotionnelController extends AbstractController
{
    #[Route(name: 'app_journal_emotionnel_index', methods: ['GET'])]
    public function index(JournalEmotionnelRepository $journalEmotionnelRepository, Request $request, ChartBuilderInterface $chartBuilder, MoodRepository $moodRepository, PaginatorInterface $paginator, StatsService $statsService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!$this->getUser() instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }
        $user = $this->getUser();

        $search = $request->query->get('search');
        $sort = $request->query->get('sort', 'dateecriture');
        $direction = $request->query->get('direction', 'desc');

        $qb = $journalEmotionnelRepository->createQueryBuilder('j');
        $qb->leftJoin('j.mood', 'm');
        $qb->andWhere('j.user = :user')
            ->setParameter('user', $user);

        if ($search) {
            $qb->andWhere('j.contenu LIKE :search OR m.humeur LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        // Valider les paramètres de tri
        $allowedSorts = ['id', 'dateecriture', 'mood'];
        $allowedDirections = ['asc', 'desc'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'dateecriture';
        }
        
        if (!in_array($direction, $allowedDirections)) {
            $direction = 'desc';
        }
        
        if ($sort === 'mood') {
            $qb->orderBy('m.humeur', $direction);
        } else {
            $qb->orderBy('j.'.$sort, $direction);
        }

        $allFilteredJournals = (clone $qb)->getQuery()->getResult();

        $journalEmotionnels = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            8
        );

        // Calculer les statistiques
        $stats = [
            'total' => count($allFilteredJournals),
            'moodCounts' => [],
            'moodPercentages' => [],
            'averageLength' => 0,
            'dates' => [],
            'moods' => [],
        ];

        if (!empty($allFilteredJournals)) {
            $totalLength = 0;
            $moodTypes = ['heureux', 'neutre', 'triste', 'colere', 'stresse', 'calme', 'fatigue', 'excite'];
            $moodCounts = array_fill_keys($moodTypes, 0);

            foreach ($allFilteredJournals as $journal) {
                if ($journal->getMood() && isset($moodCounts[$journal->getMood()->getHumeur()])) {
                    $moodCounts[$journal->getMood()->getHumeur()]++;
                }
                $totalLength += strlen($journal->getContenu() ?? '');
                $stats['dates'][] = $journal->getDateecriture()?->format('Y-m-d') ?? '';
                $stats['moods'][] = $journal->getMood()?->getHumeur() ?? 'neutre';
            }

            $stats['moodCounts'] = $moodCounts;
            $stats['averageLength'] = round($totalLength / count($allFilteredJournals));

            foreach ($moodCounts as $mood => $count) {
                $stats['moodPercentages'][$mood] = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
            }
        }

        // Build charts for journal statistics
        $journalMoodDistributionChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $journalMoodDistributionChart->setData([
            'labels' => ['😊 Heureux', '😐 Neutre', '😢 Triste', '😠 En Colère', '😰 Stressé', '😌 Calme', '😴 Fatigué', '😃 Excité'],
            'datasets' => [[
                'label' => 'Distribution des Humeurs',
                'data' => array_values($stats['moodCounts']),
                'backgroundColor' => [
                    'rgba(72, 187, 120, 0.8)',
                    'rgba(160, 174, 192, 0.8)',
                    'rgba(66, 153, 225, 0.8)',
                    'rgba(237, 100, 166, 0.8)',
                    'rgba(237, 137, 54, 0.8)',
                    'rgba(129, 230, 217, 0.8)',
                    'rgba(246, 173, 85, 0.8)',
                    'rgba(99, 179, 237, 0.8)',
                ],
                'borderColor' => [
                    'rgb(72, 187, 120)',
                    'rgb(160, 174, 192)',
                    'rgb(66, 153, 225)',
                    'rgb(237, 100, 166)',
                    'rgb(237, 137, 54)',
                    'rgb(129, 230, 217)',
                    'rgb(246, 173, 85)',
                    'rgb(99, 179, 237)',
                ],
                'borderWidth' => 2,
            ]],
        ]);
        $journalMoodDistributionChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'font' => ['size' => 12],
                        'padding' => 15,
                    ],
                ],
            ],
        ]);

        // Journal intensity chart (from associated moods)
        $allMoods = $moodRepository->findAll();
        $intensityData = [0, 0, 0, 0, 0]; // 5 intensity levels
        
        foreach ($allFilteredJournals as $journal) {
            if ($journal->getMood() && ($intensite = $journal->getMood()->getIntensite())) {
                $index = (int) $intensite - 1;
                if ($index >= 0 && $index < 5) {
                    $intensityData[$index]++;
                }
            }
        }

        $journalIntensityChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $journalIntensityChart->setData([
            'labels' => ['Très faible', 'Faible', 'Moyen', 'Élevé', 'Très élevé'],
            'datasets' => [[
                'label' => 'Intensité moyenne des émotions',
                'data' => $intensityData,
                'backgroundColor' => 'rgba(156, 39, 176, 0.8)',
                'borderColor' => 'rgb(156, 39, 176)',
                'borderWidth' => 2,
            ]],
        ]);
        $journalIntensityChart->setOptions([
            'indexAxis' => 'y',
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'labels' => ['font' => ['size' => 12]],
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
        ]);

        $gamification = $statsService->getGamificationSummary($user);
        $points = $gamification['points'];
        $badges = $gamification['badges'] !== [] ? $gamification['badges'] : ['Aucun badge pour le moment'];

        // Fetch 'metiers avancée' data
        $metiersAvancee = [
            'title' => 'Amélioration des compétences',
            'description' => 'Des outils pour rendre votre travail plus avancé et efficace.',
            'tips' => [
                'Utilisez des graphiques pour analyser vos émotions.',
                'Identifiez les tendances dans vos humeurs.',
                'Fixez des objectifs pour améliorer votre bien-être.',
            ],
        ];

        return $this->render('journal/index.html.twig', [
            'journal_emotionnels' => $journalEmotionnels,
            'stats' => $stats,
            'points' => $points,
            'badges' => $badges,
            'gamification' => $gamification,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'metiers_avancee' => $metiersAvancee,
            'journalMoodDistributionChart' => $journalMoodDistributionChart,
            'journalIntensityChart' => $journalIntensityChart,
        ]);
    }

    #[Route('/new', name: 'app_journal_emotionnel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MoodRepository $moodRepository, AIJournalService $aiService, LoggerInterface $logger, MessageBusInterface $messageBus, StatsService $statsService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $journalEmotionnel = new JournalEmotionnel();
        $form = $this->createForm(JournalEmotionnelType::class, $journalEmotionnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associate the currently authenticated user before persisting
            if (method_exists($this, 'getUser') && $this->getUser() !== null) {
                $journalEmotionnel->setUser($this->getUser());
            }

            $entityManager->persist($journalEmotionnel);
            $entityManager->flush();

            if ($this->getUser() instanceof \App\Entity\User) {
                $statsService->recomputeStats($this->getUser());
            }

            // Dispatch async PDF generation
            $messageBus->dispatch(new GenerateJournalPdfMessage(
                $journalEmotionnel->getId(),
                $this->getUser()->getId()
            ));

            // Generate AI analysis based on journal content and selected mood
            $content = $journalEmotionnel->getContenu() ?? '';
            $selectedMood = $journalEmotionnel->getMood()?->getHumeur() ?? 'neutre';
            
            // Debug: log the selected mood
            $logger->debug('Journal created with mood', [
                'mood_humeur' => $selectedMood,
                'mood_object' => $journalEmotionnel->getMood()?->getId(),
            ]);
            
            // Analyze the journal content with the selected mood (normalized to lowercase)
            $aiAnalysis = $aiService->analyzJournal($content, strtolower($selectedMood));

            return $this->redirectToRoute('app_journal_emotionnel_new_success', ['id' => $journalEmotionnel->getId()]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $logger->debug('Journal form invalid (new)', [
                'post' => $request->request->all(),
                'mood_field' => ($request->request->all('journal_emotionnel')['mood'] ?? null),
                'mood_value' => $form->has('mood') ? $form->get('mood')->getData() : null,
                'form_data' => $form->getData(),
            ]);
        }

        $debug = null;
        if ($form->isSubmitted() && !$form->isValid()) {
            $debug = [
                'mood_field_raw' => $request->request->all(),
                'mood_value' => $form->has('mood') ? $form->get('mood')->getData() : null,
                'form_data' => $form->getData(),
            ];
        }

        // Fetch all moods to build a mapping for JavaScript
        $allMoods = $moodRepository->findBy(['user' => $this->getUser()]);
        $moodMap = [];
        foreach ($allMoods as $mood) {
            $moodMap[$mood->getHumeur()] = $mood->getId();
        }

        $promptMap = $aiService->getTherapeuticPromptMap();

        return $this->render('journal/new.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'form' => $form->createView(),
            'form_debug' => $debug,
            'mood_map' => $moodMap,
            'prompt_map' => $promptMap,
            'therapeutic_prompts' => $aiService->getTherapeuticPrompts('neutre'),
            'stats' => [
                'total' => 0,
                'moodCounts' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'moodPercentages' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'averageLength' => 0,
                'dates' => [],
                'moods' => [],
            ],
        ]);
    }

    #[Route('/new/success/{id}', name: 'app_journal_emotionnel_new_success', methods: ['GET'])]
    public function newSuccess(JournalEmotionnel $journalEmotionnel, AIJournalService $aiService): Response
    {
        $content = $journalEmotionnel->getContenu() ?? '';
        $selectedMood = $journalEmotionnel->getMood()?->getHumeur() ?? 'neutre';
        $aiAnalysis = $aiService->analyzJournal($content, strtolower($selectedMood));

        return $this->render('journal/new_success.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'ai_analysis' => $aiAnalysis,
        ]);
    }

    #[Route('/{id}', name: 'app_journal_emotionnel_show', methods: ['GET'])]
    public function show(JournalEmotionnel $journalEmotionnel, MeditationService $meditationService): Response
    {
        $meditations = [];
        if ($journalEmotionnel->getMood()) {
            $meditations = $meditationService->getMeditationsByMood($journalEmotionnel->getMood()->getHumeur());
        }
        
        $affirmations = $meditationService->getAffirmations();
        $breathingExercises = $meditationService->getBreathingExercises();

        // Fetch 'metiers avancée' data
        $metiersAvancee = [
            'title' => 'Amélioration des compétences',
            'description' => 'Des outils pour rendre votre travail plus avancé et efficace.',
            'tips' => [
                'Utilisez des graphiques pour analyser vos émotions.',
                'Identifiez les tendances dans vos humeurs.',
                'Fixez des objectifs pour améliorer votre bien-être.',
            ],
        ];

        return $this->render('journal/show.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'meditations' => $meditations,
            'affirmations' => $affirmations,
            'breathing_exercises' => $breathingExercises,
            'metiers_avancee' => $metiersAvancee,
            'stats' => [
                'total' => 0,
                'moodCounts' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'moodPercentages' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'averageLength' => 0,
                'dates' => [],
                'moods' => [],
            ],
        ]);
    }

    #[Route('/{id}/edit', name: 'app_journal_emotionnel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JournalEmotionnel $journalEmotionnel, EntityManagerInterface $entityManager, MoodRepository $moodRepository, LoggerInterface $logger): Response
    {
        $form = $this->createForm(JournalEmotionnelType::class, $journalEmotionnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_emotionnel_index', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $logger->debug('Journal form invalid (edit)', [
                'post' => $request->request->all(),
                'mood_field' => ($request->request->all('journal_emotionnel')['mood'] ?? null),
                'mood_value' => $form->has('mood') ? $form->get('mood')->getData() : null,
                'form_data' => $form->getData(),
            ]);
        }

        $debug = null;
        if ($form->isSubmitted() && !$form->isValid()) {
            $debug = [
                'mood_field_raw' => $request->request->all(),
                'mood_value' => $form->has('mood') ? $form->get('mood')->getData() : null,
                'form_data' => $form->getData(),
            ];
        }

        // Fetch all moods to build a mapping for JavaScript
        $allMoods = $moodRepository->findAll();
        $moodMap = [];
        foreach ($allMoods as $mood) {
            $moodMap[$mood->getHumeur()] = $mood->getId();
        }

        return $this->render('journal/edit.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'form' => $form->createView(),
            'form_debug' => $debug,
            'mood_map' => $moodMap,
            'stats' => [
                'total' => 0,
                'moodCounts' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'moodPercentages' => ['heureux' => 0, 'neutre' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'calme' => 0, 'fatigue' => 0, 'excite' => 0],
                'averageLength' => 0,
                'dates' => [],
                'moods' => [],
            ],
        ]);
    }

    #[Route('/{id}/confirm-delete', name: 'app_journal_emotionnel_confirm_delete', methods: ['GET'])]
    public function confirmDelete(JournalEmotionnel $journalEmotionnel): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($journalEmotionnel->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer le journal d\'un autre utilisateur.');
        }

        return $this->render('journal/confirm_delete.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
        ]);
    }

    #[Route('/{id}', name: 'app_journal_emotionnel_delete', methods: ['POST'])]
    public function delete(Request $request, JournalEmotionnel $journalEmotionnel, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($journalEmotionnel->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer le journal d\'un autre utilisateur.');
        }

        $token = $request->request->get('_token');
        if (!is_string($token) || $token === '' || !$this->isCsrfTokenValid('delete'.$journalEmotionnel->getId(), $token)) {
            $this->addFlash('error', 'Requete de suppression invalide.');
            return $this->redirectToRoute('app_journal_emotionnel_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($request->request->get('confirm_delete') !== 'yes') {
            $this->addFlash('error', 'Veuillez confirmer la suppression.');
            return $this->redirectToRoute('app_journal_emotionnel_confirm_delete', ['id' => $journalEmotionnel->getId()]);
        }

        $entityManager->remove($journalEmotionnel);
        $entityManager->flush();

        return $this->redirectToRoute('app_journal_emotionnel_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/download-pdf', name: 'app_journal_emotionnel_download_pdf', methods: ['GET'])]
    public function downloadPdf(JournalEmotionnel $journalEmotionnel, #[Autowire('%kernel.project_dir%')] string $projectDir): Response
    {
        // Check user authorization
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if ($journalEmotionnel->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this journal');
        }

        // Check if PDF exists
        if (!$journalEmotionnel->getPdfPath()) {
            throw $this->createNotFoundException('PDF not yet generated');
        }

        $filePath = $projectDir . '/public/uploads/journal_pdf/' . $journalEmotionnel->getPdfPath();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('PDF file not found');
        }

        return $this->file($filePath, $journalEmotionnel->getPdfPath(), 'inline');
    }
}
