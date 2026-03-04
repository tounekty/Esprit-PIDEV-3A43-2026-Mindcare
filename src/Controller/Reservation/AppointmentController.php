<?php

namespace App\Controller\Reservation;

use App\Entity\Appointment;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Form\StudentAppointmentType;
use App\Repository\UserRepository;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository as RepoUserRepository;
use App\Service\ZoomApiService;
use App\Service\OllamaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AppointmentController extends AbstractController
{


    #[Route('/api/psychologue/{id}/availability', name: 'api_psychologue_availability', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function apiAvailability(int $id, UserRepository $userRepository, AppointmentRepository $appointmentRepository): JsonResponse
    {
        try {
            $psy = $userRepository->find($id);
            if (!$psy) {
                return new JsonResponse(['error' => 'Psychologue introuvable'], 404);
            }

            $appointments = $appointmentRepository->findBy(['psychologue' => $psy]);
            $busy = [];

            foreach ($appointments as $appointment) {
                $status = $appointment->getStatus();
                if ($status === 'accepted' || $status === 'pending') {
                    $start = $appointment->getDate();
                    if ($start instanceof \DateTimeInterface) {
                        $end = (clone $start)->modify('+1 hour');
                        $busy[] = [
                            'start' => $start->format('Y-m-d\TH:i:s'),
                            'end' => $end->format('Y-m-d\TH:i:s'),
                        ];
                    }
                }
            }

            return new JsonResponse($busy);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get AI-suggested optimal appointment times based on psychologist's patterns
     */
    #[Route('/api/psychologue/{id}/ai-suggest-times', name: 'api_psychologue_ai_suggest_times', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function aiSuggestTimes(int $id, UserRepository $userRepository, AppointmentRepository $appointmentRepository, OllamaService $ollamaService): JsonResponse
    {
        try {
            $psy = $userRepository->find($id);
            if (!$psy) {
                return new JsonResponse(['error' => 'Psychologue introuvable'], 404);
            }

            // Get this psychologist's appointments
            $appointments = $appointmentRepository->findBy(
                ['psychologue' => $psy],
                ['date' => 'DESC'],
                20 // Last 20 appointments
            );

            if (empty($appointments)) {
                return new JsonResponse([
                    'suggestions' => 'Aucune donnée disponible. Le psychologue n\'a pas encore d\'historique d\'appointments.',
                ]);
            }

            // Format appointments for AI analysis
            $appointmentData = [];
            foreach ($appointments as $appointment) {
                if ($appointment->getStatus() === 'accepted' || $appointment->getStatus() === 'completed') {
                    $appointmentData[] = [
                        'date' => $appointment->getDate(),
                        'status' => $appointment->getStatus()
                    ];
                }
            }

            if (empty($appointmentData)) {
                return new JsonResponse([
                    'suggestions' => 'Pas assez de données pour générer des suggestions. Essayez à nouveau après quelques rendez-vous complétés.',
                ]);
            }

            // Get AI suggestions based on patterns
            $psySchedule = "Disponibilité: Lundi-Vendredi, 9h-18h (à adapter selon vos préférences réelles)";
            $suggestions = $ollamaService->suggestAppointmentTimes($appointmentData, $psySchedule);

            return new JsonResponse([
                'suggestions' => $suggestions,
                'psychologue' => $psy->getFirstName() . ' ' . $psy->getLastName(),
                'analyzed_count' => count($appointmentData)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur AI: ' . $e->getMessage()], 503);
        }
    }

    /**
     * Analyze appointment patterns for a psychologist
     */
    #[Route('/api/psychologue/{id}/ai-patterns', name: 'api_psychologue_ai_patterns', methods: ['GET'], priority: 10)]
    #[IsGranted('PUBLIC_ACCESS')]
    public function aiPatterns(int $id, UserRepository $userRepository, AppointmentRepository $appointmentRepository): JsonResponse
    {
        try {
            $psy = $userRepository->find($id);
            if (!$psy) {
                return new JsonResponse(['error' => 'Psychologue introuvable'], 404);
            }

            $appointments = $appointmentRepository->findBy(
                ['psychologue' => $psy],
                ['date' => 'DESC'],
                50
            );

            if (empty($appointments)) {
                return new JsonResponse(['error' => 'Aucun rendez-vous trouvé']);
            }

            // Analyze patterns by day and time
            $patterns = [
                'by_day' => [],
                'by_hour' => [],
                'total' => count($appointments)
            ];

            foreach ($appointments as $appointment) {
                if ($appointment->getDate()) {
                    $day = $appointment->getDate()->format('l'); // Day name
                    $hour = $appointment->getDate()->format('H'); // Hour

                    $patterns['by_day'][$day] = ($patterns['by_day'][$day] ?? 0) + 1;
                    $patterns['by_hour'][$hour] = ($patterns['by_hour'][$hour] ?? 0) + 1;
                }
            }

            // Find most preferred day and time
            $mostDay = array_key_first((array) $patterns['by_day']) ?: 'Unknown';
            $mostHour = array_key_first(array_reverse((array) $patterns['by_hour'])) ?: 'Unknown';

            if (!empty($patterns['by_day'])) {
                $mostDay = array_keys($patterns['by_day'], max($patterns['by_day']))[0];
            }
            if (!empty($patterns['by_hour'])) {
                $mostHour = array_keys($patterns['by_hour'], max($patterns['by_hour']))[0];
            }

            return new JsonResponse([
                'patterns' => $patterns,
                'most_preferred_day' => $mostDay,
                'most_preferred_hour' => $mostHour . ':00',
                'recommendation' => "Ce psychologue a tendance à programmer les rendez-vous le {$mostDay} autour de {$mostHour}h."
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/reservation/new/{psyId}', name: 'reservation_new')]
    public function new(
        int $psyId,
        Request $request,
        UserRepository $userRepository,
        AppointmentRepository $appointmentRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response
    {
        $psy = $userRepository->find($psyId);
        if (!$psy) {
            throw $this->createNotFoundException('Psychologue introuvable');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Restrict psychologists from booking
        if ($this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        $appointment = new Appointment();
        $appointment->setEtudiant($user);
        $appointment->setPsychologue($psy);
        
        $form = $this->createForm(StudentAppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if student already has an appointment with this psychologue this week
            if ($appointmentRepository->hasAppointmentThisWeekWithPsychologue(
                $user,
                $psy,
                $appointment->getDate()
            )) {
                $this->addFlash('error', 'Vous avez deja un rendez-vous avec ce psychologue cette semaine. Maximum 1 par semaine.');
                return $this->render('reservation/new.html.twig', [
                    'form' => $form->createView(),
                    'psychologue' => $psy,
                ]);
            }

            // Check if the selected time is already booked for this psychologist
            $selectedDate = $appointment->getDate();
            if ($selectedDate) {
                $existingAppointments = $appointmentRepository->findBy([
                    'psychologue' => $psy
                ]);

                foreach ($existingAppointments as $existing) {
                    if ($existing->getStatus() === 'refused') {
                        continue; // Ignore refused appointments
                    }

                    $existingStart = $existing->getDate();
                    $existingEnd = (clone $existingStart)->modify('+1 hour');

                    // Check if selected time overlaps with existing appointment
                    if ($selectedDate >= $existingStart && $selectedDate < $existingEnd) {
                        $form->get('date')->addError(new FormError(
                            'Ce créneau n\'est pas disponible. Veuillez choisir une autre date.'
                        ));
                        break;
                    }
                }
            }

            // Only save if no conflicts found
            if ($form->isValid()) {
                $appointment->setStatus('pending'); // Ensure status is set to pending
                $em->persist($appointment);
                $em->flush();

                // Notify psychologue by email
                if ($psy->getEmail()) {
                    $email = (new Email())
                        ->from('noreply@mindcare.com')
                        ->to($psy->getEmail())
                        ->subject('Nouveau rendez-vous demandé')
                        ->html('<p>Bonjour ' . $psy->getFirstName() . ',</p>
                                <p>Un nouveau rendez-vous a été demandé par <strong>' . $user->getFirstName() . ' ' . $user->getLastName() . '</strong>.</p>
                                <p><strong>Date:</strong> ' . $appointment->getDate()->format('d/m/Y H:i') . '</p>
                                <p><strong>Lieu:</strong> ' . ($appointment->getLocation() == 'in_office' ? 'En cabinet' : 'En ligne') . '</p>
                                <p>Veuillez vous connecter à votre tableau de bord pour accepter ou refuser cette demande.</p>
                                <p>Cordialement,<br>L\'équipe MindCare</p>');

                    $mailer->send($email);
                }

                return $this->redirectToRoute('student_mes_rendezvous');
            }
        }

        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
            'psychologue' => $psy,
        ]);
    }



    #[Route('/reservation/{id}/accept', name: 'reservation_accept')]
    public function accept(
        int $id,
        AppointmentRepository $appointmentRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        ZoomApiService $zoomService
    ): Response {
        $appointment = $appointmentRepository->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException('Rendez-vous introuvable');
        }

        $user = $this->getUser();
        if (!$user instanceof User || $appointment->getPsychologue()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $appointment->setStatus('accepted');

        // Create Zoom meeting if appointment is online
        $zoomLink = null;
        if ($appointment->getLocation() === 'online') {
            try {
                $psychologue = $appointment->getPsychologue();
                $topic = 'Rendez-vous avec ' . $psychologue->getFirstName() . ' ' . $psychologue->getLastName();
                $description = $appointment->getDescription();

                $meetingData = $zoomService->createMeeting(
                    'me',
                    $topic,
                    $appointment->getDate(),
                    60,
                    $description
                );

                if ($meetingData['join_url']) {
                    $appointment->setZoomMeetingId($meetingData['id']);
                    $appointment->setZoomJoinUrl($meetingData['join_url']);
                    $appointment->setZoomCreatedAt(new \DateTime());
                    $zoomLink = $meetingData['join_url'];
                }
            } catch (\Exception $e) {
                // Zoom meeting creation failed, but appointment acceptance continues
                // Email will be sent without Zoom link
            }
        }

        $em->flush();

        // Notify student by email
        $student = $appointment->getEtudiant();
        if ($student && $student->getEmail()) {
            $emailContent = '<p>Bonjour ' . $student->getFirstName() . ',</p>
                        <p>Votre rendez-vous prévu le ' . $appointment->getDate()->format('d/m/Y H:i') . ' avec <strong>' . $appointment->getPsychologue()->getFirstName() . ' ' . $appointment->getPsychologue()->getLastName() . '</strong> a été accepté.</p>';

            if ($zoomLink) {
                $emailContent .= '<p><strong>Lien de réunion Zoom :</strong> <a href="' . $zoomLink . '">' . $zoomLink . '</a></p>';
            }

            $emailContent .= '<p>Cordialement,<br>L\'équipe MindCare</p>';

            $email = (new Email())
                ->from('noreply@mindcare.com')
                ->to($student->getEmail())
                ->subject('Votre rendez-vous a été accepté')
                ->html($emailContent);

            $mailer->send($email);
        }

        // ALWAYS notify psychologist by email
        $psychologue = $appointment->getPsychologue();
        if ($psychologue && $psychologue->getEmail()) {
            $emailContent = '<p>Bonjour ' . $psychologue->getFirstName() . ',</p>
                        <p>Vous avez accepté le rendez-vous avec <strong>' . $student->getFirstName() . ' ' . $student->getLastName() . '</strong> prévu le ' . $appointment->getDate()->format('d/m/Y H:i') . '.</p>';

            if ($zoomLink) {
                $emailContent .= '<p><strong>Lien de réunion Zoom :</strong> <a href="' . $zoomLink . '">' . $zoomLink . '</a></p>';
            }

            $emailContent .= '<p>Cordialement,<br>L\'équipe MindCare</p>';

            $email = (new Email())
                ->from('noreply@mindcare.com')
                ->to($psychologue->getEmail())
                ->subject('Rendez-vous accepté')
                ->html($emailContent);

            $mailer->send($email);
        }

        return $this->redirectToRoute('admin_rdv_index');
    }

    #[Route('/reservation/{id}/refuse', name: 'reservation_refuse')]
    public function refuse(int $id, AppointmentRepository $appointmentRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $appointment = $appointmentRepository->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException('Rendez-vous introuvable');
        }

        $user = $this->getUser();
        if (!$user instanceof User || $appointment->getPsychologue()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $appointment->setStatus('refused');
        $em->flush();

        // Notify student by email
        $student = $appointment->getEtudiant();
        if ($student && $student->getEmail()) {
            $email = (new Email())
                ->from('noreply@mindcare.com')
                ->to($student->getEmail())
                ->subject('Votre rendez-vous a été refusé')
                ->html('<p>Bonjour ' . $student->getFirstName() . ',</p>
                        <p>Nous vous informons que votre demande de rendez-vous avec <strong>' . $appointment->getPsychologue()->getFirstName() . ' ' . $appointment->getPsychologue()->getLastName() . '</strong> a été refusée.</p>
                        <p>Vous pouvez essayer de réserver un autre créneau dans votre espace personnel.</p>
                        <p>Cordialement,<br>L\'équipe MindCare</p>');

            $mailer->send($email);
        }

        return $this->redirectToRoute('admin_rdv_index');
    }







    #[Route('/mes-rendezvous', name: 'student_mes_rendezvous')]
    public function studentAppointments(Request $request, AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get query parameters for filtering, searching, and sorting
        $statusFilter = $request->query->get('status', 'all');
        $searchQuery = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'date');
        $sortOrder = $request->query->get('order', 'ASC');

        // Use the new repository method
        $appointments = $appointmentRepository->findByEtudiantWithFilters(
            $user,
            $statusFilter,
            $searchQuery,
            $sortBy,
            $sortOrder
        );

        return $this->render('reservation/student_mes_rendezvous.html.twig', [
            'appointments' => $appointments,
            'currentStatus' => $statusFilter,
            'currentSearch' => $searchQuery,
            'currentSort' => $sortBy,
            'currentOrder' => $sortOrder,
        ]);
    }

    #[Route('/reservation/{id}/postpone', name: 'reservation_postpone')]
    public function postpone(
        int $id,
        Request $request,
        AppointmentRepository $appointmentRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response
    {
        $appointment = $appointmentRepository->find($id);
        $user = $this->getUser();

        if (!$appointment || !$user instanceof User || $appointment->getEtudiant()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce rendez-vous.');
        }

        // Restrict psychologists from postponing
        if ($this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        // Allow postponement only for pending or accepted appointments
        if (!in_array($appointment->getStatus(), ['pending', 'accepted'])) {
            $this->addFlash('error', 'Ce rendez-vous ne peut plus être reporté.');
            return $this->redirectToRoute('student_mes_rendezvous');
        }

        $form = $this->createForm(StudentAppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for conflicts (copy logic from new action)
            $psy = $appointment->getPsychologue();
            $selectedDate = $appointment->getDate();
            
            if ($selectedDate) {
                $existingAppointments = $appointmentRepository->findBy(['psychologue' => $psy]);

                foreach ($existingAppointments as $existing) {
                    if ($existing->getId() === $appointment->getId() || $existing->getStatus() === 'refused' || $existing->getStatus() === 'cancelled') {
                        continue; 
                    }

                    $existingStart = $existing->getDate();
                    $existingEnd = (clone $existingStart)->modify('+1 hour');

                    if ($selectedDate >= $existingStart && $selectedDate < $existingEnd) {
                        $form->get('date')->addError(new FormError(
                            'Ce créneau n\'est pas disponible. Veuillez choisir une autre date.'
                        ));
                        break;
                    }
                }
            }

            if ($form->isValid()) {
                $appointment->setStatus('pending'); // Reset status to pending
                $em->flush();

                // Notify psychologue by email
                $psy = $appointment->getPsychologue();
                if ($psy && $psy->getEmail()) {
                    $email = (new Email())
                        ->from('noreply@mindcare.com')
                        ->to($psy->getEmail())
                        ->subject('Demande de report de rendez-vous')
                        ->html('<p>Bonjour ' . $psy->getFirstName() . ',</p>
                                <p>L\'étudiant <strong>' . $user->getFirstName() . ' ' . $user->getLastName() . '</strong> a demandé le report de son rendez-vous.</p>
                                <p><strong>Nouvelle Date demandée:</strong> ' . $appointment->getDate()->format('d/m/Y H:i') . '</p>
                                <p><strong>Lieu:</strong> ' . ($appointment->getLocation() == 'in_office' ? 'En cabinet' : 'En ligne') . '</p>
                                <p>Veuillez vous connecter pour traiter cette demande.</p>
                                <p>Cordialement,<br>L\'équipe MindCare</p>');

                    $mailer->send($email);
                }

                $this->addFlash('success', 'Votre demande de report a été envoyée. Le psychologue a été notifié par email.');
                return $this->redirectToRoute('student_mes_rendezvous');
            }
        }

        return $this->render('reservation/postpone.html.twig', [
            'form' => $form->createView(),
            'appointment' => $appointment,
            'psychologue' => $appointment->getPsychologue(),
        ]);
    }


    #[Route('/reservation/{id}/cancel', name: 'reservation_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request, AppointmentRepository $appointmentRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $appointment = $appointmentRepository->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException('Rendez-vous introuvable');
        }

        if ($appointment->getEtudiant()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Validate CSRF token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cancel'.$appointment->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('student_mes_rendezvous');
        }

        // Only allow canceling pending or accepted appointments
        if (!in_array($appointment->getStatus(), ['pending', 'accepted'])) {
            $this->addFlash('danger', 'Ce rendez-vous ne peut pas être annulé.');
            return $this->redirectToRoute('student_mes_rendezvous');
        }

        $appointment->setStatus('cancelled');
        $em->flush();

        // Notify psychologue by email
        $psy = $appointment->getPsychologue();
        if ($psy && $psy->getEmail()) {
            $email = (new Email())
                ->from('noreply@mindcare.com')
                ->to($psy->getEmail())
                ->subject('Rendez-vous annulé par l\'étudiant')
                ->html('<p>Bonjour ' . $psy->getFirstName() . ',</p>
                        <p>Le rendez-vous prévu le ' . ($appointment->getDate() ? $appointment->getDate()->format('d/m/Y H:i') : '-') . ' avec <strong>' . $user->getFirstName() . ' ' . $user->getLastName() . '</strong> a été annulé par l\'étudiant.</p>
                        <p>Cordialement,<br>L\'équipe MindCare</p>');

            $mailer->send($email);
        }

        $this->addFlash('success', 'Rendez-vous annulé. Le psychologue a été notifié.');
        return $this->redirectToRoute('student_mes_rendezvous');
    }
}