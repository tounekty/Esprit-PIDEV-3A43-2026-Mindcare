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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FrontEventController extends AbstractController
{
    #[Route('/events', name: 'front_events_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository, EventReservationRepository $reservationRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $sortBy = $request->query->get('sort', 'date');
        $category = $request->query->get('category', '');
        $dateFilter = $request->query->get('date', '');
        
        // Validate sort parameter for security
        if (!in_array($sortBy, ['date', 'lieu', 'date_desc'], true)) {
            $sortBy = 'date';
        }
        
        $events = $eventRepository->findBySearch(
            $query !== '' ? $query : null, 
            $sortBy, 
            $category !== '' ? $category : null
        );

        // Filter by date if provided
        if ($dateFilter !== '') {
            try {
                $filterDate = new \DateTime($dateFilter);
                $filterDateEnd = clone $filterDate;
                $filterDateEnd->setTime(23, 59, 59);
                
                $events = array_filter($events, function($event) use ($filterDate, $filterDateEnd) {
                    $eventDate = $event->getDateEvent();
                    return $eventDate >= $filterDate && $eventDate <= $filterDateEnd;
                });
            } catch (\Exception $e) {
                // Invalid date format, ignore filter
            }
        }

        $remainingById = [];
        foreach ($events as $event) {
            $activeCount = $reservationRepository->countActiveByEvent($event);
            $remainingById[$event->getId()] = max(0, $event->getCapacite() - $activeCount);
        }

        // Generate calendar availability data - ALL DAYS for 12 months
        $now = new \DateTime();
        $startDate = clone $now;
        $startDate->modify('first day of this month');
        
        $endDate = clone $now;
        $endDate->modify('+12 months');
        $endDate->modify('last day of this month');

        // First, create empty entries for all days in the date range
        $currentDay = clone $startDate;
        $calendarData = [];
        while ($currentDay <= $endDate) {
            $dateKey = $currentDay->format('Y-m-d');
            $calendarData[$dateKey] = [
                'date' => clone $currentDay,
                'total' => 0,
                'available' => 0,
                'events' => 0,
            ];
            $currentDay->modify('+1 day');
        }

        // Query all events in the date range
        $queryBuilder = $eventRepository->createQueryBuilder('e');
        $allDatedEvents = $queryBuilder
            ->where('e.dateEvent >= :startDate')
            ->andWhere('e.dateEvent <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();

        // Build calendar data with actual event information
        foreach ($allDatedEvents as $evt) {
            $dateKey = $evt->getDateEvent()->format('Y-m-d');
            if (!isset($calendarData[$dateKey])) {
                $calendarData[$dateKey] = [
                    'date' => $evt->getDateEvent(),
                    'total' => 0,
                    'available' => 0,
                    'events' => 0,
                ];
            }
            $activeReservations = $reservationRepository->countActiveByEvent($evt);
            $calendarData[$dateKey]['total'] += $evt->getCapacite();
            $calendarData[$dateKey]['available'] += max(0, $evt->getCapacite() - $activeReservations);
            $calendarData[$dateKey]['events'] += 1;
        }

        // Sort calendar data by date
        ksort($calendarData);

        // Get user's reserved event dates for calendar highlighting
        $userReservedDates = [];
        $user = $this->getUser();
        if ($user instanceof User) {
            $userReservedDates = $reservationRepository->findReservedEventDatesByUser($user);
        }

        return $this->render('front/event/index.html.twig', [
            'events' => $events,
            'q' => $query,
            'sortBy' => $sortBy,
            'category' => $category,
            'date' => $dateFilter,
            'remainingById' => $remainingById,
            'calendarData' => $calendarData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'userReservedDates' => $userReservedDates,
        ]);
    }

    #[Route('/events/{id}', name: 'front_events_show', methods: ['GET'])]
    public function show(Event $event, EventReservationRepository $reservationRepository, EventRepository $eventRepository): Response
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
                'action' => $this->generateUrl('front_events_reserve_post', ['id' => $event->getId()]),
                'method' => 'POST',
            ])->createView();
        }

        // Generate calendar availability data
        $eventDate = $event->getDateEvent();
        $startDate = \DateTime::createFromInterface($eventDate);
        $startDate->modify('-1 month');
        $startDate->modify('first day of this month');
        
        $endDate = \DateTime::createFromInterface($eventDate);
        $endDate->modify('+1 month');
        $endDate->modify('last day of this month');

        // First, create empty entries for all days in the date range
        $currentDay = clone $startDate;
        $calendarData = [];
        while ($currentDay <= $endDate) {
            $dateKey = $currentDay->format('Y-m-d');
            $calendarData[$dateKey] = [
                'date' => clone $currentDay,
                'total' => 0,
                'available' => 0,
                'events' => 0,
            ];
            $currentDay->modify('+1 day');
        }

        // Query events in the date range
        $query = $eventRepository->createQueryBuilder('e')
            ->where('e.dateEvent >= :startDate')
            ->andWhere('e.dateEvent <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.dateEvent', 'ASC')
            ->getQuery()
            ->getResult();

        // Build availability calendar
        foreach ($query as $evt) {
            $dateKey = $evt->getDateEvent()->format('Y-m-d');
            if (!isset($calendarData[$dateKey])) {
                $calendarData[$dateKey] = [
                    'date' => $evt->getDateEvent(),
                    'total' => 0,
                    'available' => 0,
                    'events' => 0,
                ];
            }
            $activeReservations = $reservationRepository->countActiveByEvent($evt);
            $calendarData[$dateKey]['total'] += $evt->getCapacite();
            $calendarData[$dateKey]['available'] += max(0, $evt->getCapacite() - $activeReservations);
            $calendarData[$dateKey]['events'] += 1;
        }

        // Sort calendar data by date
        ksort($calendarData);

        return $this->render('front/event/show.html.twig', [
            'event' => $event,
            'activeCount' => $activeCount,
            'remaining' => $remaining,
            'userReservation' => $userReservation,
            'canReserve' => $canReserve,
            'form' => $formView,
            'calendarData' => $calendarData,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    #[Route('/events/{id}/reserve', name: 'front_events_reserve', methods: ['GET'])]
    public function reserveGet(Event $event): Response
    {
        // Redirect GET requests back to the event page where the form is
        return $this->redirectToRoute('front_events_show', ['id' => $event->getId()]);
    }

    #[Route('/events/{id}/reserve', name: 'front_events_reserve_post', methods: ['POST'])]
    public function reserve(Event $event, Request $request, EventReservationRepository $reservationRepository, EventRepository $eventRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
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
        // Set these BEFORE handleRequest so entity validation passes
        $reservation->setEvent($event);
        $reservation->setUser($user);
        $reservation->reserveNow();
        $reservation->setStatut(EventReservation::STATUS_PENDING);

        $form = $this->createForm(EventReservationType::class, $reservation, [
            'action' => $this->generateUrl('front_events_reserve_post', ['id' => $event->getId()]),
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

        if (!$form->isSubmitted() || !$form->isValid()) {
            // Return form with errors
            $activeCount = $reservationRepository->countActiveByEvent($event);
            $remaining = max(0, $event->getCapacite() - $activeCount);
            $userReservation = $reservationRepository->findLatestByUserAndEvent($user, $event);
            $hasActiveReservation = $userReservation && in_array($userReservation->getStatut(), [
                EventReservation::STATUS_PENDING,
                EventReservation::STATUS_ACCEPTED,
            ], true);

            // Generate calendar availability data
            $eventDate = $event->getDateEvent();
            $startDate = \DateTime::createFromInterface($eventDate);
            $startDate->modify('-1 month');
            $startDate->modify('first day of this month');
            
            $endDate = \DateTime::createFromInterface($eventDate);
            $endDate->modify('+1 month');
            $endDate->modify('last day of this month');

            $currentDay = clone $startDate;
            $calendarData = [];
            while ($currentDay <= $endDate) {
                $dateKey = $currentDay->format('Y-m-d');
                $calendarData[$dateKey] = [
                    'date' => clone $currentDay,
                    'total' => 0,
                    'available' => 0,
                    'events' => 0,
                ];
                $currentDay->modify('+1 day');
            }

            $query = $eventRepository->createQueryBuilder('e')
                ->where('e.dateEvent >= :startDate')
                ->andWhere('e.dateEvent <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->orderBy('e.dateEvent', 'ASC')
                ->getQuery()
                ->getResult();

            foreach ($query as $evt) {
                $dateKey = $evt->getDateEvent()->format('Y-m-d');
                if (!isset($calendarData[$dateKey])) {
                    $calendarData[$dateKey] = [
                        'date' => $evt->getDateEvent(),
                        'total' => 0,
                        'available' => 0,
                        'events' => 0,
                    ];
                }
                $activeReservations = $reservationRepository->countActiveByEvent($evt);
                $calendarData[$dateKey]['total'] += $evt->getCapacite();
                $calendarData[$dateKey]['available'] += max(0, $evt->getCapacite() - $activeReservations);
                $calendarData[$dateKey]['events'] += 1;
            }

            ksort($calendarData);

            return $this->render('front/event/show.html.twig', [
                'event' => $event,
                'activeCount' => $activeCount,
                'remaining' => $remaining,
                'userReservation' => $userReservation,
                'canReserve' => !$hasActiveReservation && $remaining > 0,
                'form' => $form->createView(),
                'calendarData' => $calendarData,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
        }

        // Check capacity
        $activeCount = $reservationRepository->countActiveByEvent($event);
        if ($activeCount >= $event->getCapacite()) {
            $this->addFlash('error', 'Désolé, cet événement est complet.');
            return $this->redirectToRoute('front_events_show', ['id' => $event->getId()]);
        }

        // Generate confirmation token
        $token = bin2hex(random_bytes(32));

        // Save reservation as PENDING with token
        $reservation->setConfirmationToken($token);

        $em->persist($reservation);
        $em->flush();

        // Send confirmation email
        $confirmUrl = $this->generateUrl('front_events_confirm_reservation', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $emailBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background-color: #f0f9ff; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); color: white; padding: 30px; border-radius: 12px 12px 0 0; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .event-details { background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0284c7; }
        .event-details p { margin: 8px 0; }
        .confirm-btn { display: inline-block; background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); color: white !important; padding: 16px 40px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 16px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Confirmez votre réservation</h1>
        </div>
        <div class="content">
            <p>Bonjour <strong>{$reservation->getPrenom()} {$reservation->getNom()}</strong>,</p>
            <p>Vous avez demandé une réservation pour l'événement suivant :</p>
            <div class="event-details">
                <p><strong>Événement :</strong> {$event->getTitre()}</p>
                <p><strong>Date :</strong> {$event->getDateEvent()->format('d/m/Y à H:i')}</p>
                <p><strong>Lieu :</strong> {$event->getLieu()}</p>
            </div>
            <p>Pour confirmer votre réservation, veuillez cliquer sur le bouton ci-dessous :</p>
            <p style="text-align: center;">
                <a href="{$confirmUrl}" class="confirm-btn">Confirmer ma réservation</a>
            </p>
            <p style="font-size: 13px; color: #64748b;">Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
            <a href="{$confirmUrl}">{$confirmUrl}</a></p>
        </div>
        <div class="footer">
            <p>© 2026 MindCare - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
HTML;

        try {
            $email = (new Email())
                ->from('sarahlaroussi65@gmail.com')
                ->to($user->getEmail())
                ->subject('Confirmez votre réservation - ' . $event->getTitre())
                ->html($emailBody);

            $mailer->send($email);
            $this->addFlash('success', 'Un email de confirmation a été envoyé à ' . $user->getEmail() . '. Veuillez vérifier votre boîte de réception pour confirmer votre réservation.');
        } catch (\Exception $e) {
            // Show the actual error so we can debug
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('front_events_show', ['id' => $event->getId()]);
    }

    #[Route('/reservation/confirm/{token}', name: 'front_events_confirm_reservation', methods: ['GET'])]
    public function confirmReservation(string $token, EventReservationRepository $reservationRepository, EntityManagerInterface $em): Response
    {
        $reservation = $reservationRepository->findByConfirmationToken($token);

        if (!$reservation) {
            $this->addFlash('error', 'Lien de confirmation invalide ou expiré.');
            return $this->redirectToRoute('front_events_index');
        }

        if ($reservation->getStatut() === EventReservation::STATUS_ACCEPTED) {
            $this->addFlash('info', 'Votre réservation a déjà été confirmée.');
            return $this->redirectToRoute('front_events_show', ['id' => $reservation->getEvent()->getId()]);
        }

        if ($reservation->getStatut() !== EventReservation::STATUS_PENDING) {
            $this->addFlash('error', 'Cette réservation ne peut plus être confirmée.');
            return $this->redirectToRoute('front_events_index');
        }

        // Check capacity one more time
        $event = $reservation->getEvent();
        $activeCount = $reservationRepository->countActiveByEvent($event);
        // Subtract 1 because this pending reservation is already counted in active
        $confirmedCount = (int) $reservationRepository->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.event = :event')
            ->andWhere('r.statut = :status')
            ->setParameter('event', $event)
            ->setParameter('status', EventReservation::STATUS_ACCEPTED)
            ->getQuery()
            ->getSingleScalarResult();

        if ($confirmedCount >= $event->getCapacite()) {
            $this->addFlash('error', 'Désolé, cet événement est désormais complet.');
            $reservation->setStatut(EventReservation::STATUS_CANCELLED);
            $reservation->setConfirmationToken(null);
            $em->flush();
            return $this->redirectToRoute('front_events_show', ['id' => $event->getId()]);
        }

        // Confirm the reservation
        $reservation->setStatut(EventReservation::STATUS_ACCEPTED);
        $reservation->setConfirmationToken(null);
        $em->flush();

        $this->addFlash('success', 'Votre réservation pour "' . $event->getTitre() . '" a été confirmée avec succès !');
        return $this->redirectToRoute('front_my_reservations');
    }

    #[Route('/mes-reservations', name: 'front_my_reservations', methods: ['GET'])]
    public function myReservations(EventReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Get all reservations for the current user
        $reservations = $reservationRepository->findBy(['user' => $user], ['dateReservation' => 'DESC']);

        // Group by status
        $reservationsByStatus = [
            'confirmed' => [],
            'pending' => [],
            'refused' => [],
            'cancelled' => [],
        ];

        foreach ($reservations as $reservation) {
            $status = $reservation->getStatut();
            
            if ($status === EventReservation::STATUS_ACCEPTED) {
                $reservationsByStatus['confirmed'][] = $reservation;
            } elseif ($status === EventReservation::STATUS_PENDING) {
                $reservationsByStatus['pending'][] = $reservation;
            } elseif ($status === EventReservation::STATUS_REFUSED) {
                $reservationsByStatus['refused'][] = $reservation;
            } elseif ($status === EventReservation::STATUS_CANCELLED) {
                $reservationsByStatus['cancelled'][] = $reservation;
            }
        }

        return $this->render('front/event/my_reservations.html.twig', [
            'reservations' => $reservations,
            'reservationsByStatus' => $reservationsByStatus,
            'totalReservations' => count($reservations),
        ]);
    }
}

