<?php

namespace App\Controller;

use App\Entity\Mood;
use App\Form\MoodType;
use App\Repository\MoodRepository;
use App\Repository\JournalEmotionnelRepository;
use App\Service\MeditationService;
use App\Service\AIJournalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mood')]
final class MoodController extends AbstractController
{
    #[Route(name: 'app_mood_index', methods: ['GET'])]
    public function index(MoodRepository $moodRepository, JournalEmotionnelRepository $journalRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $sort = $request->query->get('sort', 'datemood');
        $direction = $request->query->get('direction', 'desc');

        $qb = $moodRepository->createQueryBuilder('m');

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

        $moods = $qb->getQuery()->getResult();

        // Charger les données du journal émotionnel
        $journalEmotionnels = $journalRepository->findAll();

        // Préparer les données pour les graphiques
        $dates = [];
        $intensites = [];
        $humeurs = [];
        $moodCounts = [
            'heureux' => 0,
            'triste' => 0,
            'colere' => 0,
            'stresse' => 0,
            'neutre' => 0
        ];

        foreach ($moods as $mood) {
            $dates[] = $mood->getDatemood() ? $mood->getDatemood()->format('Y-m-d') : '';
            $intensites[] = $mood->getIntensite();
            $humeurs[] = $mood->getHumeur();
            if (isset($moodCounts[$mood->getHumeur()])) {
                $moodCounts[$mood->getHumeur()]++;
            }
        }

        // Calculer les statistiques
        $stats = [
            'total' => count($moods),
            'moodCounts' => $moodCounts,
            'moodPercentages' => [],
            'averageIntensity' => 0,
            'maxIntensity' => 0,
            'minIntensity' => 0,
        ];

        if (!empty($moods)) {
            $totalIntensity = array_sum($intensites);
            $stats['averageIntensity'] = round($totalIntensity / count($moods), 1);
            $stats['maxIntensity'] = max($intensites);
            $stats['minIntensity'] = min($intensites);

            foreach ($moodCounts as $mood => $count) {
                $stats['moodPercentages'][$mood] = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
            }
        }

        // Fetch points and badges
        $points = 100; // Example points, replace with actual logic
        $badges = ['Beginner', 'Explorer']; // Example badges, replace with actual logic

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

        return $this->render('mood/index.html.twig', [
            'moods' => $moods,
            'stats' => $stats,
            'points' => $points,
            'badges' => $badges,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'metiers_avancee' => $metiersAvancee,
        ]);
    }

    #[Route('/new', name: 'app_mood_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AIJournalService $aiService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $mood = new Mood();
        $mood->setUser($this->getUser());
        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($mood);
            $entityManager->flush();

            // Analyze mood with AI
            $aiAnalysis = $aiService->analyzJournal($mood->getHumeur());

            return $this->render('mood/new_success.html.twig', [
                'mood' => $mood,
                'ai_analysis' => $aiAnalysis,
                'stats' => [
                    'total' => 0,
                    'moodCounts' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                    'moodPercentages' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                    'averageIntensity' => 0,
                ],
            ]);
        }

        return $this->render('mood/new.html.twig', [
            'mood' => $mood,
            'form' => $form,
            'stats' => [
                'total' => 0,
                'moodCounts' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                'moodPercentages' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                'averageIntensity' => 0,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_mood_show', methods: ['GET'])]
    public function show(Mood $mood, MeditationService $meditationService): Response
    {
        $meditations = $meditationService->getMeditationsByMood($mood->getHumeur());
        $affirmations = $meditationService->getAffirmations();
        $breathingExercises = $meditationService->getBreathingExercises();

        return $this->render('mood/show.html.twig', [
            'mood' => $mood,
            'meditations' => $meditations,
            'affirmations' => $affirmations,
            'breathing_exercises' => $breathingExercises,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mood_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mood $mood, EntityManagerInterface $entityManager): Response
    {
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

    #[Route('/{id}', name: 'app_mood_delete', methods: ['POST'])]
    public function delete(Request $request, Mood $mood, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mood->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($mood);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
    }
}
