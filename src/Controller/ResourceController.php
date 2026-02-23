<?php

namespace App\Controller;

use App\Entity\Resource;
use App\Entity\Commentaire;
use App\Form\CommentaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/resources')]
class ResourceController extends AbstractController
{
    #[Route('/', name: 'resource_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $resources = $em->getRepository(Resource::class)->findBy([], ['createdAt' => 'DESC']);
        return $this->render('resource/index.html.twig', [
            'resources' => $resources,
        ]);
    }

    #[Route('/{id}', name: 'resource_show')]
    public function show(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        $commentForm = null;

        if ($this->getUser()) {
            $user = $this->getUser();

            $commentaire = new Commentaire();
            $commentaire->setAuthorName($user->getFirstName() . ' ' . $user->getLastName());
            $commentaire->setAuthorEmail($user->getEmail() ?? '');
            $commentaire->setResource($resource);
            $commentaire->setUser($user);

            $form = $this->createForm(CommentaireType::class, $commentaire);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $commentaire->setCreatedAt(new \DateTimeImmutable());
                $commentaire->setApproved(true); // now auto-approved

                $em->persist($commentaire);
                $em->flush();

                $this->addFlash('success', 'Commentaire ajouté avec succès!');
                return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
            }

            $commentForm = $form->createView();
        }

        return $this->render('resource/show.html.twig', [
            'resource' => $resource,
            'commentForm' => $commentForm,
            'all_resources' => $em->getRepository(Resource::class)->findAll(),
        ]);
    }
}
