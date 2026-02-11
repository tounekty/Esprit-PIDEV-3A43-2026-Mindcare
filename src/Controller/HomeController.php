<?php

<<<<<<< HEAD
=======
// src/Controller/HomeController.php

>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
<<<<<<< HEAD
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
=======
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AppointmentRepository;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        return $this->render('home/index.html.twig', [
            'bannedUntil' => $user?->getBannedUntil(),
            'isBanned'    => $user?->isBanned(),
        ]);
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
    }
}
