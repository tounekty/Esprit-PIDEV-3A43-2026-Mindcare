<?php

namespace App\Controller;

use App\Entity\JournalEmotionnel;
use App\Form\JournalEmotionnelType;
use App\Repository\JournalEmotionnelRepository;
use App\Service\MeditationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/journal/emotionnel')]
final class JournalEmotionnelController extends AbstractController
{
    #[Route(name: 'app_journal_emotionnel_index', methods: ['GET'])]
    public function index(JournalEmotionnelRepository $journalEmotionnelRepository): Response
    {
        return $this->render('journal_emotionnel/index.html.twig', [
            'journal_emotionnels' => $journalEmotionnelRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_journal_emotionnel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $journalEmotionnel = new JournalEmotionnel();
        $journalEmotionnel->setDateecriture(new \DateTime());
        $form = $this->createForm(JournalEmotionnelType::class, $journalEmotionnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($journalEmotionnel);
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_emotionnel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('journal_emotionnel/new.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'form' => $form,
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

        return $this->render('journal_emotionnel/show.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'meditations' => $meditations,
            'affirmations' => $affirmations,
            'breathing_exercises' => $breathingExercises,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_journal_emotionnel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JournalEmotionnel $journalEmotionnel, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JournalEmotionnelType::class, $journalEmotionnel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_journal_emotionnel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('journal_emotionnel/edit.html.twig', [
            'journal_emotionnel' => $journalEmotionnel,
            'form' => $form,
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
