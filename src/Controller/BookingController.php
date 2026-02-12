<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/booking')]
class BookingController extends AbstractController
{
    #[Route(name: 'app_booking_index', methods: ['GET'])]
    public function index(Request $request, BookingRepository $bookingRepository, EventRepository $eventRepository): Response
    {
        return $this->render('booking/index.html.twig', [
            'bookings' => $bookingRepository->findBySearch(
                $request->query->get('nom'),
                $request->query->get('lieu'),
                $request->query->get('tri', 'nomEtudiant')
            ),
            'events' => $eventRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_booking_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EventRepository $eventRepository): Response
    {
        $booking = new Booking();
        // Initialisation de la date (obligatoire selon ton entité)
        $booking->setDateReservation(new \DateTimeImmutable());
        // Pré-sélectionner un événement si fourni en query string (?event=ID)
        $eventId = $request->query->get('event');
        if ($eventId) {
            $event = $eventRepository->find($eventId);
            if ($event) {
                $booking->setEvent($event);
            }
        }
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event = $booking->getEvent();

                // Vérification de la capacité de l'atelier
                if ($event && count($event->getBookings()) >= $event->getCapaciteMax()) {
                    $this->addFlash('danger', 'Désolé, cet atelier est complet !');
                } else {
                    $entityManager->persist($booking);
                    $entityManager->flush();

                    $this->addFlash('success', 'Réservation enregistrée avec succès !');
                    return $this->redirectToRoute('app_booking_index');
                }
            } else {
                // DEBUG : Affiche les erreurs de validation si l'enregistrement échoue
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('danger', 'Erreur : ' . $error->getMessage());
                }
            }
        }

        return $this->render('booking/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_booking_delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$booking->getId(), $request->request->get('_token'))) {
            $entityManager->remove($booking);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation annulée.');
        }
        return $this->redirectToRoute('app_booking_index');
    }
    #[Route('/{id}/edit', name: 'app_booking_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        // On réutilise le même formulaire que pour la création
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pas besoin de persist() ici car l'objet vient déjà de la BDD
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a été mise à jour !');
            return $this->redirectToRoute('app_booking_index');
        }

        return $this->render('booking/edit.html.twig', [
            'booking' => $booking,
            'form' => $form,
        ]);
    }
    #[Route('/{id}', name: 'app_booking_show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }
}