<?php

namespace App\Controller;

use App\Entity\PatientFile;
use App\Entity\User;
use App\Form\StudentPatientFileType;
use App\Form\FullPatientFileType;
use App\Repository\PatientFileRepository;
use App\Repository\UserRepository;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PatientFileController extends AbstractController
{
    /**
     * Student view/edit of their own medical info
     */
    #[Route('/mon-dossier-medical', name: 'student_patient_file')]
    public function studentFile(Request $request, EntityManagerInterface $em, AppointmentRepository $appointmentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ETUDIANT');
        $user = $this->getUser();

        $patientFile = $user->getPatientFile();
        if (!$patientFile) {
            $patientFile = new PatientFile();
            $patientFile->setStudent($user);
            // Don't persist yet, only if they save
        }

        $form = $this->createForm(StudentPatientFileType::class, $patientFile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Link all existing appointments to this dossier
            $appointments = $appointmentRepository->findBy(['etudiant' => $user]);
            foreach ($appointments as $appointment) {
                $appointment->setPatientFile($patientFile);
            }
            
            $em->persist($patientFile);
            $em->flush();
            $this->addFlash('success', 'Votre dossier médical a été mis à jour.');
            return $this->redirectToRoute('student_patient_file');
        }

        return $this->render('patient_file/student_view.html.twig', [
            'form' => $form->createView(),
            'patientFile' => $patientFile
        ]);
    }

    /**
     * Admin/Psy list of all students and their dossiers
     */
    #[Route('/admin/dossiers', name: 'admin_patient_file_index')]
    public function index(Request $request, PatientFileRepository $patientFileRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE'); // Admin has this too usually

        $search = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'name');
        $sortOrder = $request->query->get('order', 'ASC');

        $students = $patientFileRepository->findAllStudentsWithFiles($search, $sortBy, $sortOrder);

        return $this->render('patient_file/index.html.twig', [
            'students' => $students,
            'currentSearch' => $search,
            'currentSort' => $sortBy,
            'currentOrder' => $sortOrder
        ]);
    }

    /**
     * Admin/Psy view and Psy full edit
     */
    #[Route('/admin/dossier/etudiant/{id}', name: 'admin_patient_file_show')]
    public function show(
        int $id, 
        Request $request, 
        UserRepository $userRepository, 
        AppointmentRepository $appointmentRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');
        
        $student = $userRepository->find($id);
        if (!$student || !in_array('ROLE_ETUDIANT', $student->getRoles())) {
            throw $this->createNotFoundException('Étudiant introuvable');
        }

        $patientFile = $student->getPatientFile();
        $isNew = false;

        if (!$patientFile) {
            $patientFile = new PatientFile();
            $patientFile->setStudent($student);
            $isNew = true;
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isPsy = $this->isGranted('ROLE_PSYCHOLOGUE') && !$isAdmin;

        // Admin can edit context, Psy can edit everything
        $form = $this->createForm(FullPatientFileType::class, $patientFile, [
            'is_admin' => $isAdmin
        ]);

        $form->handleRequest($request);

        if (($isPsy || $isAdmin) && $form->isSubmitted() && $form->isValid()) {
            // Link all existing appointments to this dossier
            $appointments = $appointmentRepository->findBy(['etudiant' => $student]);
            foreach ($appointments as $appointment) {
                $appointment->setPatientFile($patientFile);
            }
            
            $em->persist($patientFile);
            $em->flush();
            $this->addFlash('success', 'Le dossier a été mis à jour.');
            return $this->redirectToRoute('admin_patient_file_show', ['id' => $id]);
        }

        return $this->render('patient_file/show.html.twig', [
            'form' => $form->createView(),
            'student' => $student,
            'patientFile' => $patientFile,
            'isAdmin' => $isAdmin,
            'isNew' => $isNew
        ]);
    }

    /**
     * Psy only: Delete a patient file
     */
    #[Route('/admin/dossier/etudiant/{id}/delete', name: 'admin_patient_file_delete', methods: ['POST'])]
    public function delete(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PSYCHOLOGUE');
        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('L\'administrateur ne peut pas supprimer de dossiers cliniques.');
        }

        $student = $userRepository->find($id);
        $patientFile = $student ? $student->getPatientFile() : null;

        if ($patientFile) {
            $em->remove($patientFile);
            $em->flush();
            $this->addFlash('success', 'Le dossier clinique a été supprimé.');
        }

        return $this->redirectToRoute('admin_patient_file_index');
    }
}
