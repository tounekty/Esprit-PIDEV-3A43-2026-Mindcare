<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController extends AbstractController
{
    #[Route('/redirect', name: 'app_redirect')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            // Not logged in, send to login
            return $this->redirectToRoute('app_login');
        }

        if ($this->isGranted('ROLE_ETUDIANT')) {
            return $this->redirectToRoute('app_home');
        }

        if ($this->isGranted('ROLE_PSYCHOLOGUE')) {
            return $this->redirectToRoute('app_admin');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        }

        // fallback: send to home if role unknown
        return $this->redirectToRoute('app_home');
    }
}
