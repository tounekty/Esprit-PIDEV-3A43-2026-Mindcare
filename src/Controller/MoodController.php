<?php

namespace App\Controller;

use App\Entity\Mood;
use App\Form\MoodType;
use App\Repository\MoodRepository;
use App\Repository\JournalEmotionnelRepository;
use App\Service\MeditationService;
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
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'asc');

        $qb = $moodRepository->createQueryBuilder('m');

        if ($search) {
            $qb->andWhere('m.humeur LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        // Valider les paramètres de tri
        $allowedSorts = ['id', 'humeur', 'intensite', 'datemood'];
        $allowedDirections = ['asc', 'desc'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'id';
        }
        
        if (!in_array($direction, $allowedDirections)) {
            $direction = 'asc';
        }
        
        $qb->orderBy('m.'.$sort, $direction);

        $moods = $qb->getQuery()->getResult();

        // Charger les données du journal émotionnel
        $journalEmotionnels = $journalRepository->findAll();

        // Préparer les données pour les graphiques
        $dates = [];
        $intensites = [];
        $humeurs = [];

        foreach ($moods as $mood) {
            $dates[] = $mood->getDatemood() ? $mood->getDatemood()->format('Y-m-d') : '';
            $intensites[] = $mood->getIntensite();
            $humeurs[] = $mood->getHumeur();
        }

        return $this->render('mood/index.html.twig', [
            'moods' => $moods,
            'journal_emotionnels' => $journalEmotionnels,
            'dates' => json_encode($dates),
            'intensites' => json_encode($intensites),
            'humeurs' => json_encode($humeurs),
            'direction' => $direction,
            'sort' => $sort,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_mood_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $mood = new Mood();
        $mood->setUser($this->getUser());
        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($mood);
            $entityManager->flush();

            return $this->redirectToRoute('app_mood_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mood/new.html.twig', [
            'mood' => $mood,
            'form' => $form,
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
