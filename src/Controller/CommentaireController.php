<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commentaire')]
class CommentaireController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/{id}/delete', name: 'commentaire_delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $commentaire): Response
    {
        $resourceId = $commentaire->getResource()?->getId();
        $redirect = null !== $resourceId
            ? $this->redirectToRoute('resource_show', ['id' => $resourceId])
            : $this->redirectToRoute('resource_index');

        if (!$this->isCsrfTokenValid('delete' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');

            return $redirect;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez etre connecte.');
        }

        $isOwner = null !== $commentaire->getUser() && $commentaire->getUser()->getId() === $user->getId();
        if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos commentaires.');
        }

        $this->entityManager->remove($commentaire);
        $this->entityManager->flush();
        $this->addFlash('success', 'Commentaire supprime avec succes.');

        return $redirect;
    }
}

