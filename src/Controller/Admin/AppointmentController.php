<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\User;
<<<<<<< HEAD
use App\Form\AppointmentReportType;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use App\Service\ZoomApiService;
use App\Service\OllamaService;
=======
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
>>>>>>> origin/sara
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AppointmentController extends AbstractController
{
    #[Route('/admin/rdv', name: 'admin_rdv_index')]
    public function index(Request $request, AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Get query parameters for filtering, searching, and sorting
        $statusFilter = $request->query->get('status', 'all');
        $searchQuery = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'date');
        $sortOrder = $request->query->get('order', 'DESC');

        // Check if psychologist or admin
        if ($this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN')) {
            $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');
            
            // Use the new flexible query method
            $appointments = $appointmentRepository->findByPsychologueWithFilters(
                $user, 
                $statusFilter, 
                $searchQuery, 
                $sortBy, 
                $sortOrder
            );
            
            // Calculate counts for dashboard
            $pendingCount = $appointmentRepository->countByPsychologueAndStatus($user, 'pending');
            $acceptedCount = $appointmentRepository->countByPsychologueAndStatus($user, 'accepted');
            $refusedCount = $appointmentRepository->countByPsychologueAndStatus($user, 'refused');
            $cancelledCount = $appointmentRepository->countByPsychologueAndStatus($user, 'cancelled');
<<<<<<< HEAD
            $inProgressCount = $appointmentRepository->countByPsychologueAndStatus($user, 'in_progress');
            $completedCount = $appointmentRepository->countByPsychologueAndStatus($user, 'completed');
            $archivedCount = $appointmentRepository->countByPsychologueAndStatus($user, 'archived');
            $absentCount = $appointmentRepository->countByPsychologueAndStatus($user, 'absent');
=======
>>>>>>> origin/sara
            
            // Get accepted appointments for calendar
            $acceptedAppointments = $appointmentRepository->findBy(['psychologue' => $user, 'status' => 'accepted'], ['date' => 'ASC']);
            
            return $this->render('admin/rdv/index.html.twig', [
                'appointments' => $appointments,
                'pendingCount' => $pendingCount,
                'acceptedCount' => $acceptedCount,
                'refusedCount' => $refusedCount,
                'cancelledCount' => $cancelledCount,
<<<<<<< HEAD
                'inProgressCount' => $inProgressCount,
                'completedCount' => $completedCount,
                'archivedCount' => $archivedCount,
                'absentCount' => $absentCount,
=======
>>>>>>> origin/sara
                'acceptedAppointments' => $acceptedAppointments,
                'isPsychologue' => true,
                'currentStatus' => $statusFilter,
                'currentSearch' => $searchQuery,
                'currentSort' => $sortBy,
                'currentOrder' => $sortOrder,
            ]);
        } else {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            
            // Use the new global filters method for admins
            $appointments = $appointmentRepository->findAllWithFilters(
                $statusFilter,
                $searchQuery,
                $sortBy,
                $sortOrder
            );
            
            // Calculate counts for admin dashboard
            $pendingCount = count($appointmentRepository->findBy(['status' => 'pending']));
            $acceptedCount = count($appointmentRepository->findBy(['status' => 'accepted']));
            $refusedCount = count($appointmentRepository->findBy(['status' => 'refused']));
            $cancelledCount = count($appointmentRepository->findBy(['status' => 'cancelled']));
<<<<<<< HEAD
            $inProgressCount = count($appointmentRepository->findBy(['status' => 'in_progress']));
            $completedCount = count($appointmentRepository->findBy(['status' => 'completed']));
            $archivedCount = count($appointmentRepository->findBy(['status' => 'archived']));
            $absentCount = count($appointmentRepository->findBy(['status' => 'absent']));
=======
>>>>>>> origin/sara
            
            return $this->render('admin/rdv/index.html.twig', [
                'appointments' => $appointments,
                'pendingCount' => $pendingCount,
                'acceptedCount' => $acceptedCount,
                'refusedCount' => $refusedCount,
                'cancelledCount' => $cancelledCount,
<<<<<<< HEAD
                'inProgressCount' => $inProgressCount,
                'completedCount' => $completedCount,
                'archivedCount' => $archivedCount,
                'absentCount' => $absentCount,
=======
>>>>>>> origin/sara
                'isPsychologue' => false,
                'currentStatus' => $statusFilter,
                'currentSearch' => $searchQuery,
                'currentSort' => $sortBy,
                'currentOrder' => $sortOrder,
            ]);
        }
    }

    #[Route('/admin/rdv/new', name: 'admin_rdv_new', methods: ['GET','POST'])]
<<<<<<< HEAD
    public function new(Request $request, EntityManagerInterface $em, AppointmentRepository $appointmentRepository): Response
=======
    public function new(Request $request, EntityManagerInterface $em): Response
>>>>>>> origin/sara
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $appointment = new Appointment();
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
<<<<<<< HEAD
            // Check if student already has an appointment with this psychologue this week
            if ($appointmentRepository->hasAppointmentThisWeekWithPsychologue(
                $appointment->getEtudiant(),
                $appointment->getPsychologue(),
                $appointment->getDate()
            )) {
                $this->addFlash('error', 'Cet etudiant a deja un rendez-vous avec ce psychologue cette semaine.');
                return $this->render('admin/rdv/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

=======
>>>>>>> origin/sara
            $em->persist($appointment);
            $em->flush();

            return $this->redirectToRoute('admin_rdv_index');
        }

        return $this->render('admin/rdv/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/rdv/{id}', name: 'admin_rdv_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, AppointmentRepository $appointmentRepository): Response
    {
        $appointment = $appointmentRepository->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException('Rendez-vous not found');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN')) {
            // Psychologist can only view their own appointments
            if ($appointment->getPsychologue()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }
        } else {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

<<<<<<< HEAD
        $reportForm = null;
        $canUploadReport = false;

        // Show report upload form for psychologists on completed appointments
        if ($this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN')) {
            if ($appointment->getPsychologue()->getId() === $user->getId() 
                && in_array($appointment->getStatus(), ['completed', 'archived'])) {
                $canUploadReport = true;
                $reportForm = $this->createForm(AppointmentReportType::class, $appointment);
            }
        }

        return $this->render('admin/rdv/show.html.twig', [
            'appointment' => $appointment,
            'reportForm' => $reportForm ? $reportForm->createView() : null,
            'canUploadReport' => $canUploadReport,
        ]);
    }

    #[Route('/admin/rdv/{id}/report', name: 'admin_rdv_report_upload', methods: ['POST'])]
    public function uploadReport(Request $request, Appointment $appointment, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Only psychologists (not admins) can upload
        if (!$this->isGranted('ROLE_PSYCHOLOGUE') || $this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        // Only for their own appointments
        if ($appointment->getPsychologue()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Only after completion
        if (!in_array($appointment->getStatus(), ['completed', 'archived'])) {
            $this->addFlash('error', 'Le compte rendu peut etre ajoute uniquement apres un rendez-vous termine.');
            return $this->redirectToRoute('admin_rdv_show', ['id' => $appointment->getId()]);
        }

        $form = $this->createForm(AppointmentReportType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid() && $form->get('reportFile')->getData() !== null) {
                $em->flush();
                $this->addFlash('success', 'Le compte rendu a ete televerse avec succes.');
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Erreur: ' . implode(', ', $errors ?: ['Veuillez selectionner un fichier.']));
            }
        }

        return $this->redirectToRoute('admin_rdv_show', ['id' => $appointment->getId()]);
    }

=======
        return $this->render('admin/rdv/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

>>>>>>> origin/sara
    #[Route('/admin/rdv/pending', name: 'admin_rdv_pending')]
    public function pending(Request $request, AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');

        // Get filter and search parameters
        $searchQuery = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'date');
        $sortOrder = $request->query->get('order', 'ASC');

        // Admins see all pending appointments, psychologists see only theirs
        if ($this->isGranted('ROLE_ADMIN')) {
            $appointments = $appointmentRepository->findAllWithFilters(
                'pending',
                $searchQuery,
                $sortBy,
                $sortOrder
            );
        } else {
            $appointments = $appointmentRepository->findByPsychologueWithFilters(
                $user,
                'pending',
                $searchQuery,
                $sortBy,
                $sortOrder
            );
        }

        return $this->render('admin/rdv/pending.html.twig', [
            'appointments' => $appointments,
            'currentSearch' => $searchQuery,
            'currentSort' => $sortBy,
            'currentOrder' => $sortOrder,
        ]);
    }

    #[Route('/admin/rdv/{id}/accept', name: 'admin_rdv_accept', methods: ['POST'])]
<<<<<<< HEAD
    public function accept(
        Appointment $appointment,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        ZoomApiService $zoomService
    ): Response {
=======
    public function accept(Appointment $appointment, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
>>>>>>> origin/sara
        $user = $this->getUser();
        // Allow if current psychologist OR if admin
        if (!$user instanceof User || ($appointment->getPsychologue()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException();
        }

        $appointment->setStatus('accepted');
<<<<<<< HEAD

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

=======
>>>>>>> origin/sara
        $em->flush();

        // Notify student by email
        $student = $appointment->getEtudiant();
        if ($student && $student->getEmail()) {
<<<<<<< HEAD
            $emailContent = '<p>Bonjour ' . $student->getFirstName() . ',</p>
                        <p>Votre rendez-vous prévu le ' . $appointment->getDate()->format('d/m/Y H:i') . ' avec <strong>' . $appointment->getPsychologue()->getFirstName() . ' ' . $appointment->getPsychologue()->getLastName() . '</strong> a été accepté.</p>';

            if ($zoomLink) {
                $emailContent .= '<p><strong>Lien de réunion Zoom :</strong> <a href="' . $zoomLink . '">' . $zoomLink . '</a></p>';
            }

            $emailContent .= '<p>Cordialement,<br>L\'équipe MindCare</p>';

=======
>>>>>>> origin/sara
            $email = (new Email())
                ->from('noreply@mindcare.com')
                ->to($student->getEmail())
                ->subject('Votre rendez-vous a été accepté')
<<<<<<< HEAD
                ->html($emailContent);

            $mailer->send($email);
        }

        // ALWAYS notify psychologist by email
        $psychologue = $appointment->getPsychologue();
        if ($psychologue && $psychologue->getEmail()) {
            $emailContent = '<p>Bonjour ' . $psychologue->getFirstName() . ',</p>';
            
            if ($user->getId() === $psychologue->getId()) {
                // Psychologist accepted their own appointment
                $emailContent .= '<p>Vous avez accepté le rendez-vous avec <strong>' . $student->getFirstName() . ' ' . $student->getLastName() . '</strong> prévu le ' . $appointment->getDate()->format('d/m/Y H:i') . '.</p>';
            } else {
                // Admin accepted on behalf of psychologist
                $emailContent .= '<p>Un rendez-vous avec <strong>' . $student->getFirstName() . ' ' . $student->getLastName() . '</strong> prévu le ' . $appointment->getDate()->format('d/m/Y H:i') . ' a été accepté par l\'administration.</p>';
            }

            if ($zoomLink) {
                $emailContent .= '<p><strong>Lien de réunion Zoom :</strong> <a href="' . $zoomLink . '">' . $zoomLink . '</a></p>';
            }

            $emailContent .= '<p>Cordialement,<br>L\'équipe MindCare</p>';

            $email = (new Email())
                ->from('noreply@mindcare.com')
                ->to($psychologue->getEmail())
                ->subject('Rendez-vous accepté')
                ->html($emailContent);
=======
                ->html('<p>Bonjour ' . $student->getFirstName() . ',</p>
                        <p>Votre rendez-vous prévu le ' . $appointment->getDate()->format('d/m/Y H:i') . ' avec <strong>' . $appointment->getPsychologue()->getFirstName() . ' ' . $appointment->getPsychologue()->getLastName() . '</strong> a été accepté.</p>
                        <p>Cordialement,<br>L\'équipe MindCare</p>');
>>>>>>> origin/sara

            $mailer->send($email);
        }

        return $this->redirectToRoute('admin_rdv_pending');
    }

    #[Route('/admin/rdv/{id}/decline', name: 'admin_rdv_decline', methods: ['POST'])]
    public function decline(Appointment $appointment, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        // Allow if current psychologist OR if admin
        if (!$user instanceof User || ($appointment->getPsychologue()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN'))) {
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

        return $this->redirectToRoute('admin_rdv_pending');
    }

<<<<<<< HEAD
    #[Route('/admin/rdv/{id}/mark-absent', name: 'admin_rdv_mark_absent', methods: ['POST'])]
    public function markAbsent(Appointment $appointment, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        
        // Only psychologist of this appointment can mark absent
        if (!$user instanceof User || $appointment->getPsychologue()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Can only mark absent if in_progress or completed
        if (!in_array($appointment->getStatus(), ['in_progress', 'completed'])) {
            $this->addFlash('error', 'Impossible de marquer comme absent. Statut actuel : ' . $appointment->getStatus());
            return $this->redirectToRoute('admin_rdv_index');
        }

        $appointment->setStatus('absent');
        $em->flush();

        // Notify student by email
        $student = $appointment->getEtudiant();
        if ($student && $student->getEmail()) {
            $email = (new Email())
                ->from('noreply@mindcare.com')
                ->to($student->getEmail())
                ->subject('Rendez-vous manqué')
                ->html('<p>Bonjour ' . $student->getFirstName() . ',</p>
                        <p>Nous constatons que vous n\'avez pas assisté à votre rendez-vous prévu le ' . $appointment->getDate()->format('d/m/Y H:i') . ' avec <strong>' . $appointment->getPsychologue()->getFirstName() . ' ' . $appointment->getPsychologue()->getLastName() . '</strong>.</p>
                        <p>Si vous avez eu un empêchement, merci de nous contacter pour reprogrammer.</p>
                        <p>Cordialement,<br>L\'équipe MindCare</p>');

            $mailer->send($email);
        }

        $this->addFlash('success', 'Rendez-vous marqué comme "Patient absent".');
        return $this->redirectToRoute('admin_rdv_index');
    }

    #[Route('/admin/rdv/{id}/undo-absent', name: 'admin_rdv_undo_absent', methods: ['POST'])]
    public function undoAbsent(Appointment $appointment, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        // Only psychologist of this appointment can undo
        if (!$user instanceof User || $appointment->getPsychologue()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($appointment->getStatus() !== 'absent') {
            $this->addFlash('error', 'Ce rendez-vous n\'est pas marqué comme absent.');
            return $this->redirectToRoute('admin_rdv_index');
        }

        // Return to completed (safest default)
        $appointment->setStatus('completed');
        $em->flush();

        $this->addFlash('success', 'Statut "Absent" annulé. Rendez-vous marqué comme terminé.');
        return $this->redirectToRoute('admin_rdv_index');
    }

    #[Route('/admin/rdv/{id}/edit', name: 'admin_rdv_edit', methods: ['GET','POST'])]
    #[Route('/admin/rdv/{id}/edit', name: 'admin_rdv_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $em, MailerInterface $mailer, AppointmentRepository $appointmentRepository): Response
=======
    #[Route('/admin/rdv/{id}/edit', name: 'admin_rdv_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $em, MailerInterface $mailer): Response
>>>>>>> origin/sara
    {
        $user = $this->getUser();
        
        // Check if psychologist can edit their own or admin editing
        if ($this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN')) {
            // Psychologist can only edit their own appointments
            if ($appointment->getPsychologue()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }
        } else {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $isPsychologue = $this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(AppointmentType::class, $appointment, ['is_psychologue' => $isPsychologue]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
<<<<<<< HEAD
            // Check if student already has an appointment with this psychologue this week (excluding current)
            if ($appointmentRepository->hasAppointmentThisWeekWithPsychologue(
                $appointment->getEtudiant(),
                $appointment->getPsychologue(),
                $appointment->getDate(),
                $appointment->getId()
            )) {
                $this->addFlash('error', 'Cet etudiant a deja un rendez-vous avec ce psychologue cette semaine.');
                return $this->render('admin/rdv/edit.html.twig', [
                    'form' => $form->createView(),
                    'appointment' => $appointment,
                ]);
            }

=======
>>>>>>> origin/sara
            $appointment->setStatus('pending');
            $em->flush();
            
            // Notify the student about the update via email
            $student = $appointment->getEtudiant();
            if ($student && $student->getEmail()) {
                $email = (new Email())
                    ->from('noreply@mindcare.com')
                    ->to($student->getEmail())
                    ->subject('Votre rendez-vous a été mis à jour')
                    ->html('<p>Bonjour ' . $student->getFirstName() . ',</p>
                            <p>Votre rendez-vous avec <strong>' . $appointment->getPsychologue()->getFirstName() . ' ' . $appointment->getPsychologue()->getLastName() . '</strong> a été modifié par l\'administration.</p>
                            <p><strong>Nouvelle Date:</strong> ' . $appointment->getDate()->format('d/m/Y H:i') . '</p>
                            <p><strong>Lieu:</strong> ' . ($appointment->getLocation() == 'in_office' ? 'En cabinet' : 'En ligne') . '</p>
                            <p>Veuillez vous connecter à votre compte pour voir les détails complets.</p>
                            <p>Cordialement,<br>L\'équipe MindCare</p>');
                
                $mailer->send($email);
            }
            
            $this->addFlash('success', 'Rendez-vous mis à jour avec succès. L\'étudiant a été notifié par email.');
            
            return $this->redirectToRoute('admin_rdv_index');
        }

        return $this->render('admin/rdv/edit.html.twig', [
            'form' => $form->createView(),
            'appointment' => $appointment,
        ]);
    }

    #[Route('/admin/rdv/{id}/delete', name: 'admin_rdv_delete', methods: ['POST'])]
    public function delete(Request $request, Appointment $appointment, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        
        // Check if psychologist can delete their own or admin deleting
        if ($this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN')) {
            // Psychologist can only delete their own appointments
            if ($appointment->getPsychologue()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }
        } else {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        if ($this->isCsrfTokenValid('delete'.$appointment->getId(), $request->request->get('_token'))) {
            $student = $appointment->getEtudiant();
            $appointmentDate = $appointment->getDate();
            
            // Send email notification to student before deleting
            if ($student && $student->getEmail()) {
                $email = (new Email())
                    ->from('noreply@mindcare.com')
                    ->to($student->getEmail())
                    ->subject('Votre rendez-vous a été annulé')
                    ->html('<p>Bonjour ' . $student->getFirstName() . ',</p>
                            <p>Votre rendez-vous prévu le ' . $appointmentDate->format('d/m/Y H:i') . ' avec <strong>' . $appointment->getPsychologue()->getFirstName() . ' ' . $appointment->getPsychologue()->getLastName() . '</strong> a été annulé.</p>
                            <p>Veuillez nous contacter si vous avez des questions.</p>
                            <p>Cordialement,<br>L\'équipe MindCare</p>');
                
                $mailer->send($email);
            }
            
            $em->remove($appointment);
            $em->flush();
            
            $this->addFlash('warning', 'Appointment deleted successfully. Student has been notified via email.');
        }

        return $this->redirectToRoute('admin_rdv_index');
    }
<<<<<<< HEAD

    /**
     * AI: Suggest optimal appointment times
     */
    #[Route('/admin/rdv/ai/suggest-times', name: 'admin_rdv_ai_suggest_times', methods: ['POST'])]
    public function aiSuggestTimes(Request $request, AppointmentRepository $appointmentRepository, OllamaService $ollamaService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');
        
        $psychologueId = $request->request->get('psychologue_id');
        if (!$psychologueId) {
            return $this->json(['error' => 'Psychologue ID is required'], 400);
        }

        $existingAppointments = $appointmentRepository->findBy(
            ['psychologue' => $psychologueId, 'status' => 'accepted'],
            ['date' => 'DESC'],
            10
        );

        $appointmentsData = array_map(function($apt) {
            return ['date' => $apt->getDate()];
        }, $existingAppointments);

        $schedule = "Available: Monday-Friday 9:00-17:00"; // Could be dynamic from psychologist profile
        $suggestions = $ollamaService->suggestAppointmentTimes($appointmentsData, $schedule);
        
        return $this->json(['suggestions' => $suggestions]);
    }
=======
>>>>>>> origin/sara
}
