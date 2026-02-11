<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/', name: 'app_')]
class FrontController extends AbstractController
{
    #[Route('', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig', [
            'title' => 'Accueil',
        ]);
    }

    #[Route('about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig', [
            'title' => 'À propos',
        ]);
    }

    #[Route('events-legacy', name: 'events_legacy')]
    public function eventsLegacy(): Response
    {
        return $this->redirectToRoute('front_events_index');
    }

    #[Route('blog', name: 'blog')]
    public function blog(): Response
    {
        return $this->render('front/blog.html.twig', [
            'title' => 'Blog',
        ]);
    }

    #[Route('contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('front/contact.html.twig', [
            'title' => 'Contact',
        ]);
    }

    #[Route('dashboard', name: 'dashboard')]
    #[IsGranted('IS_AUTHENTICATED')]
    public function dashboard(): Response
    {
        return $this->render('front/dashboard.html.twig', [
            'title' => 'Mon Tableau de Bord',
        ]);
    }
}
