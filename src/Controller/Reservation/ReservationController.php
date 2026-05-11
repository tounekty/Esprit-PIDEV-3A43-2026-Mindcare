<?php
// src/Controller/Reservation/ReservationController.php
namespace App\Controller\Reservation;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'app_reservation')]
    public function index(UserRepository $userRepository): Response
    {
        // Restrict access for Psychologists
        if ($this->isGranted('ROLE_PSYCHOLOGUE') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        // Get all users with role 'psychologue'
        $psychologues = $userRepository->findBy(['role' => 'psychologue']);

        return $this->render('reservation/list.html.twig', [
            'psychologues' => $psychologues
        ]);
    }
}
