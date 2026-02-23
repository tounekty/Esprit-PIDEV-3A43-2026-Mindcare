<?php

namespace App\Controller\Admin;

use App\Entity\EventReservation;
use App\Repository\EventReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/event-reservations', name: 'admin_event_reservations_')]
class EventReservationController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, EventReservationRepository $reservationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        $status = $request->query->get('status', 'all');
        $search = (string) $request->query->get('search', '');
        $sortBy = (string) $request->query->get('sort', 'dateReservation');
        $sortOrder = (string) $request->query->get('order', 'DESC');

        return $this->render('admin/gestion_event_reservations/index.html.twig', [
            'reservations' => $reservationRepository->findByFilters($status, $search, $sortBy, $sortOrder),
            'currentStatus' => $status,
            'currentSearch' => $search,
            'currentSort' => $sortBy,
            'currentOrder' => $sortOrder,
        ]);
    }

    #[Route('/{id}/accept', name: 'accept', methods: ['POST'])]
    public function accept(EventReservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        if ($this->isCsrfTokenValid('event_reservation_accept_' . $reservation->getId(), (string) $request->request->get('_token'))) {
            $reservation->setStatut(EventReservation::STATUS_ACCEPTED);
            $em->flush();
            $this->addFlash('success', 'Reservation acceptee.');
        }

        return $this->redirectToRoute('admin_event_reservations_index');
    }

    #[Route('/{id}/refuse', name: 'refuse', methods: ['POST'])]
    public function refuse(EventReservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        if ($this->isCsrfTokenValid('event_reservation_refuse_' . $reservation->getId(), (string) $request->request->get('_token'))) {
            $reservation->setStatut(EventReservation::STATUS_REFUSED);
            $em->flush();
            $this->addFlash('success', 'Reservation refusee.');
        }

        return $this->redirectToRoute('admin_event_reservations_index');
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(EventReservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        if ($this->isCsrfTokenValid('event_reservation_cancel_' . $reservation->getId(), (string) $request->request->get('_token'))) {
            $reservation->setStatut(EventReservation::STATUS_CANCELLED);
            $em->flush();
            $this->addFlash('success', 'Reservation annulee.');
        }

        return $this->redirectToRoute('admin_event_reservations_index');
    }
}
