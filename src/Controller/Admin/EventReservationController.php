<?php

namespace App\Controller\Admin;

use App\Entity\EventReservation;
use App\Repository\EventReservationRepository;
use App\Service\TwilioSmsService;
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

    #[Route('/send-reminders', name: 'send_reminders', methods: ['POST'])]
    public function sendReminders(Request $request, EventReservationRepository $reservationRepository, TwilioSmsService $smsService, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        if (!$this->isCsrfTokenValid('send_sms_reminders', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_event_reservations_index');
        }

        $reservations = $reservationRepository->findUpcomingForSmsReminder(24);

        if (empty($reservations)) {
            $this->addFlash('info', 'Aucun rappel SMS a envoyer (pas de reservations confirmees pour les prochaines 24h).');
            return $this->redirectToRoute('admin_event_reservations_index');
        }

        $sent = 0;
        $failed = 0;

        foreach ($reservations as $reservation) {
            $event = $reservation->getEvent();
            $phone = $reservation->getTelephone();

            if (!$phone) {
                $failed++;
                continue;
            }

            $message = sprintf(
                "Rappel MindCare: Votre evenement \"%s\" est prevu le %s a %s. Lieu: %s. A bientot!",
                $event->getTitre(),
                $event->getDateEvent()->format('d/m/Y'),
                $event->getDateEvent()->format('H:i'),
                $event->getLieu()
            );

            if ($smsService->sendSms($phone, $message)) {
                $reservation->setSmsReminderSent(true);
                $sent++;
            } else {
                $failed++;
                $lastSmsError = $smsService->getLastError();
                if ($lastSmsError) {
                    $this->addFlash('error', "SMS vers {$phone} a echoue: {$lastSmsError}");
                }
            }
        }

        $em->flush();

        if ($sent > 0) {
            $this->addFlash('success', "{$sent} rappel(s) SMS envoye(s) avec succes.");
        }
        if ($failed > 0) {
            $this->addFlash('warning', "{$failed} rappel(s) SMS ont echoue.");
        }

        return $this->redirectToRoute('admin_event_reservations_index');
    }
}
