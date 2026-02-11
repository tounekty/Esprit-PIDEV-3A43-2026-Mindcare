<?php

namespace App\Controller\Reservation;

use App\Entity\Appointment;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Form\StudentAppointmentType;
use App\Repository\UserRepository;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository as RepoUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
                        $form->get('date')->addError(new \Symfony\Component\Form\FormError(
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
    public function accept(int $id, AppointmentRepository $appointmentRepository, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $appointment = $appointmentRepository->find($id);
        if (!$appointment) {
            throw $this->createNotFoundException('Rendez-vous introuvable');
        }

        $user = $this->getUser();
        if (!$user instanceof User || $appointment->getPsychologue()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $appointment->setStatus('accepted');
        $em->flush();

        // Notify student by email
        $student = $appointment->getEtudiant();
        if ($student && $student->getEmail()) {
            $email = (new Email())
                ->from('noreply@mindcare.com')
                ->to($student->getEmail())
                ->subject('Votre rendez-vous a été accepté')
                ->html('<p>Bonjour ' . $student->getFirstName() . ',</p>
                        <p>Votre rendez-vous prévu le ' . $appointment->getDate()->format('d/m/Y H:i') . ' avec <strong>' . $appointment->getPsychologue()->getFirstName() . ' ' . $appointment->getPsychologue()->getLastName() . '</strong> a été accepté.</p>
                        <p>Cordialement,<br>L\'équipe MindCare</p>');

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
                        $form->get('date')->addError(new \Symfony\Component\Form\FormError(
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
