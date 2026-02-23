<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventReservation;
use App\Entity\User;
use App\Form\EventReservationType;
use App\Repository\EventRepository;
use App\Repository\EventReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontEventController extends AbstractController
{
    #[Route('/events', name: 'front_events_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository, EventReservationRepository $reservationRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $events = $eventRepository->findBySearch($query !== '' ? $query : null);

        $remainingById = [];
        foreach ($events as $event) {
            $activeCount = $reservationRepository->countActiveByEvent($event);
            $remainingById[$event->getId()] = max(0, $event->getCapacite() - $activeCount);
        }

        return $this->render('front/event/index.html.twig', [
            'events' => $events,
            'q' => $query,
            'remainingById' => $remainingById,
        ]);
    }

    #[Route('/events/{id}', name: 'front_events_show', methods: ['GET'])]
    public function show(Event $event, EventReservationRepository $reservationRepository): Response
    {
        $activeCount = $reservationRepository->countActiveByEvent($event);
        $remaining = max(0, $event->getCapacite() - $activeCount);

        $userReservation = null;
        $canReserve = false;

        $user = $this->getUser();
        $formView = null;

        if ($user instanceof User) {
            $userReservation = $reservationRepository->findLatestByUserAndEvent($user, $event);
            $hasActiveReservation = $userReservation && in_array($userReservation->getStatut(), [
                EventReservation::STATUS_PENDING,
                EventReservation::STATUS_ACCEPTED,
            ], true);

            $isStudent = $user->getRole() === 'etudiant';
            $canReserve = $isStudent && !$hasActiveReservation && $remaining > 0;

            $reservation = new EventReservation();
            $formView = $this->createForm(EventReservationType::class, $reservation, [
                'action' => $this->generateUrl('front_events_reserve', ['id' => $event->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render('front/event/show.html.twig', [
            'event' => $event,
            'activeCount' => $activeCount,
            'remaining' => $remaining,
            'userReservation' => $userReservation,
            'canReserve' => $canReserve,
            'form' => $formView,
        ]);
    }

    #[Route('/events/{id}/reserve', name: 'front_events_reserve', methods: ['POST'])]
    public function reserve(Event $event, Request $request, EventReservationRepository $reservationRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->getRole() !== 'etudiant') {
            $this->addFlash('error', 'Seuls les etudiants peuvent reserver un evenement.');
            return $this->redirectToRoute('front_events_show', ['id' => $event->getId()]);
        }

        $reservation = new EventReservation();
        $form = $this->createForm(EventReservationType::class, $reservation, [
            'action' => $this->generateUrl('front_events_reserve', ['id' => $event->getId()]),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        $latestReservation = $reservationRepository->findLatestByUserAndEvent($user, $event);
        if ($latestReservation && in_array($latestReservation->getStatut(), [
            EventReservation::STATUS_PENDING,
            EventReservation::STATUS_ACCEPTED,
        ], true)) {
            $this->addFlash('error', 'Vous avez deja une reservation active pour cet evenement.');
            return $this->redirectToRoute('front_events_show', ['id' => $event->getId()]);
        }

        $activeCount = $reservationRepository->countActiveByEvent($event);
        if ($activeCount >= $event->getCapacite()) {
            $this->addFlash('error', 'Desole, la capacite est atteinte.');
            return $this->redirectToRoute('front_events_show', ['id' => $event->getId()]);
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            $remaining = max(0, $event->getCapacite() - $activeCount);
            $userReservation = $reservationRepository->findLatestByUserAndEvent($user, $event);
            $hasActiveReservation = $userReservation && in_array($userReservation->getStatut(), [
                EventReservation::STATUS_PENDING,
                EventReservation::STATUS_ACCEPTED,
            ], true);

            return $this->render('front/event/show.html.twig', [
                'event' => $event,
                'activeCount' => $activeCount,
                'remaining' => $remaining,
                'userReservation' => $userReservation,
                'canReserve' => !$hasActiveReservation && $remaining > 0,
                'form' => $form->createView(),
            ]);
        }

        $reservation->setEvent($event);
        $reservation->setUser($user);
        $reservation->setDateReservation(new \DateTime());
        $reservation->setStatut(EventReservation::STATUS_PENDING);

        $em->persist($reservation);
        $em->flush();

        $this->addFlash('success', 'Votre reservation a ete envoyee.');

        return $this->redirectToRoute('front_events_show', ['id' => $event->getId()]);
    }
}
