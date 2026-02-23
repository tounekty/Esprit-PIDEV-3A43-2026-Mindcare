<?php

namespace App\Controller;

use App\Entity\JournalEmotionnel;
use App\Form\JournalEmotionnelType;
use App\Repository\JournalEmotionnelRepository;
use App\Service\MeditationService;
use App\Service\MoodProviderService;
use App\Service\StatsService;
use App\Service\AIJournalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/journal/emotionnel')]
final class JournalEmotionnelController extends AbstractController
{
    #[Route(name: 'app_journal_emotionnel_index', methods: ['GET'])]
    public function index(JournalEmotionnelRepository $journalEmotionnelRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $sort = $request->query->get('sort', 'dateecriture');
        $direction = $request->query->get('direction', 'desc');

        $qb = $journalEmotionnelRepository->createQueryBuilder('j');
        $qb->leftJoin('j.mood', 'm');

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

        $journalEmotionnels = $qb->getQuery()->getResult();

        // Calculer les statistiques
        $stats = [
            'total' => count($journalEmotionnels),
            'moodCounts' => [],
            'moodPercentages' => [],
            'averageLength' => 0,
            'dates' => [],
            'moods' => [],
        ];

        if (!empty($journalEmotionnels)) {
            $totalLength = 0;
            $moodCounts = [
                'heureux' => 0,
                'triste' => 0,
                'colere' => 0,
                'stresse' => 0,
                'neutre' => 0
            ];

            foreach ($journalEmotionnels as $journal) {
                if ($journal->getMood() && isset($moodCounts[$journal->getMood()->getHumeur()])) {
                    $moodCounts[$journal->getMood()->getHumeur()]++;
                }
                $totalLength += strlen($journal->getContenu() ?? '');
                $stats['dates'][] = $journal->getDateecriture()?->format('Y-m-d') ?? '';
                $stats['moods'][] = $journal->getMood()?->getHumeur() ?? 'neutre';
            }

            $stats['moodCounts'] = $moodCounts;
            $stats['averageLength'] = round($totalLength / count($journalEmotionnels));

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
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'metiers_avancee' => $metiersAvancee,
        ]);
    }

    #[Route('/new', name: 'app_journal_emotionnel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $journalEmotionnel = new JournalEmotionnel();
        $form = $this->createForm(JournalEmotionnelType::class, $journalEmotionnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($journalEmotionnel);
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_emotionnel_index', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $logger->debug('Journal form invalid (new)', [
                'post' => $request->request->all(),
                'mood_field' => $request->request->get('journal')['mood'] ?? null,
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

        return $this->render('journal/new.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'form' => $form->createView(),
            'form_debug' => $debug,
            'stats' => [
                'total' => 0,
                'moodCounts' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                'moodPercentages' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                'averageLength' => 0,
                'dates' => [],
                'moods' => [],
            ],
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
                'moodCounts' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                'moodPercentages' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                'averageLength' => 0,
                'dates' => [],
                'moods' => [],
            ],
        ]);
    }

    #[Route('/{id}/edit', name: 'app_journal_emotionnel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JournalEmotionnel $journalEmotionnel, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $form = $this->createForm(JournalEmotionnelType::class, $journalEmotionnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_emotionnel_index', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $logger->debug('Journal form invalid (edit)', [
                'post' => $request->request->all(),
                'mood_field' => $request->request->get('journal')['mood'] ?? null,
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

        return $this->render('journal/edit.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'form' => $form->createView(),
            'form_debug' => $debug,
            'stats' => [
                'total' => 0,
                'moodCounts' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                'moodPercentages' => ['heureux' => 0, 'triste' => 0, 'colere' => 0, 'stresse' => 0, 'neutre' => 0],
                'averageLength' => 0,
                'dates' => [],
                'moods' => [],
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_journal_emotionnel_delete', methods: ['POST'])]
    public function delete(Request $request, JournalEmotionnel $journalEmotionnel, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$journalEmotionnel->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($journalEmotionnel);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_journal_emotionnel_index', [], Response::HTTP_SEE_OTHER);
    }
}
