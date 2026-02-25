<?php

namespace App\Controller;

use App\Entity\PsychologicalAlert;
use App\Repository\PsychologicalAlertRepository;
use App\Service\PsychologicalAlertService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/psychological-alerts')]
final class PsychologicalAlertController extends AbstractController
{
    #[Route(name: 'admin_psychological_alerts_index', methods: ['GET'])]
    public function index(PsychologicalAlertRepository $alertRepository, Request $request): Response
    {
        $this->denyAccessToAlerts();

        $filter = $request->query->get('filter', 'unresolved');
        $page = (int) $request->query->get('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        if ($filter === 'unresolved') {
            $alerts = $alertRepository->findUnresolvedAlerts();
        } else {
            $alerts = $alertRepository->findAll();
        }

        // Simple pagination
        $totalAlerts = count($alerts);
        $alerts = array_slice($alerts, $offset, $limit);

        return $this->render('admin/psychological_alert/index.html.twig', [
            'alerts' => $alerts,
            'filter' => $filter,
            'page' => $page,
            'totalPages' => ceil($totalAlerts / $limit),
            'totalAlerts' => $totalAlerts,
        ]);
    }

    #[Route('/{id}', name: 'admin_psychological_alert_show', methods: ['GET'])]
    public function show(PsychologicalAlert $alert): Response
    {
        $this->denyAccessToAlerts();

        return $this->render('admin/psychological_alert/show.html.twig', [
            'alert' => $alert,
        ]);
    }

    #[Route('/{id}/resolve', name: 'admin_psychological_alert_resolve', methods: ['POST'])]
    public function resolve(
        PsychologicalAlert $alert,
        Request $request,
        PsychologicalAlertService $alertService,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessToAlerts();

        $adminNotes = $request->request->get('admin_notes', '');
        $alertService->resolveAlert($alert, $adminNotes);

        $this->addFlash('success', 'Alerte resolue. Un email de suivi et de conseils a ete envoye a l\'etudiant.');

        return $this->redirectToRoute('admin_psychological_alerts_index');
    }

    private function denyAccessToAlerts(): void
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PSYCHOLOGUE')) {
            throw $this->createAccessDeniedException('You must be an admin or psychologist to view alerts.');
        }
    }
}

