<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();
        $bannedUntil = null;

        if ($user && method_exists($user, 'getBannedUntil')) {
            $bannedUntil = $user->getBannedUntil();
        }

        return $this->render('home/index.html.twig', [
            'bannedUntil' => $bannedUntil
        ]);
    }
}
