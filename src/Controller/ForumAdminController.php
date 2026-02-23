<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/forum')]
final class ForumAdminController extends AbstractController
{
    #[Route('/sujets', name: 'sujet_forum_index', methods: ['GET'])]
    public function sujets(): Response
    {
        return $this->render('admin/forum_sujets.html.twig');
    }

    #[Route('/messages', name: 'message_forum_index', methods: ['GET'])]
    public function messages(): Response
    {
        return $this->render('admin/forum_messages.html.twig');
    }
}
