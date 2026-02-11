<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $user = $this->getUser();
        $roles = $user->getRoles();
        
        // Redirect psychologists to their appointment list
        if (in_array('ROLE_PSYCHOLOGUE', $roles) && !in_array('ROLE_ADMIN', $roles)) {
            return $this->redirectToRoute('admin_rdv_pending');
        }
        
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
}
