<?php

namespace App\Controller\Admin;

use App\Entity\Resource;
use App\Form\ResourceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/ressources', name: 'admin_resources_')]
class AdminResourceController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(EntityManagerInterface $em): Response
    {
        $resources = $em->getRepository(Resource::class)->findBy([], ['id' => 'DESC']);

        return $this->render('admin/gestion_resources/index.html.twig', [
            'resources' => $resources,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $resource = new Resource();
        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resource->setCreatedAt(new \DateTimeImmutable());
            $em->persist($resource);
            $em->flush();

            $this->addFlash('success', 'Ressource créée avec succès !');
            return $this->redirectToRoute('admin_resources_index');
        }

        return $this->render('admin/gestion_resources/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Resource $resource, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Ressource mise à jour avec succès !');
            return $this->redirectToRoute('admin_resources_index');
        }

        return $this->render('admin/gestion_resources/edit.html.twig', [
            'form' => $form,
            'resource' => $resource,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Resource $resource, EntityManagerInterface $em): Response
    {
        // CSRF check
        if ($this->isCsrfTokenValid('delete'.$resource->getId(), $request->request->get('_token'))) {
            $em->remove($resource);
            $em->flush();
            $this->addFlash('success', 'Ressource supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide, suppression annulée !');
        }

        return $this->redirectToRoute('admin_resources_index');
    }
}
