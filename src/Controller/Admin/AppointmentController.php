<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
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
            
            // Get accepted appointments for calendar
            $acceptedAppointments = $appointmentRepository->findBy(['psychologue' => $user, 'status' => 'accepted'], ['date' => 'ASC']);
            
            return $this->render('admin/rdv/index.html.twig', [
                'appointments' => $appointments,
                'pendingCount' => $pendingCount,
                'acceptedCount' => $acceptedCount,
                'refusedCount' => $refusedCount,
                'cancelledCount' => $cancelledCount,
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
            
            return $this->render('admin/rdv/index.html.twig', [
                'appointments' => $appointments,
                'pendingCount' => $pendingCount,
                'acceptedCount' => $acceptedCount,
                'refusedCount' => $refusedCount,
                'cancelledCount' => $cancelledCount,
                'isPsychologue' => false,
                'currentStatus' => $statusFilter,
                'currentSearch' => $searchQuery,
                'currentSort' => $sortBy,
                'currentOrder' => $sortOrder,
            ]);
        }
    }

    #[Route('/admin/rdv/new', name: 'admin_rdv_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $appointment = new Appointment();
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

        return $this->render('admin/rdv/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

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
    public function accept(Appointment $appointment, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        // Allow if current psychologist OR if admin
        if (!$user instanceof User || ($appointment->getPsychologue()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN'))) {
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

    #[Route('/admin/rdv/{id}/edit', name: 'admin_rdv_edit', methods: ['GET','POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $em, MailerInterface $mailer): Response
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
}
