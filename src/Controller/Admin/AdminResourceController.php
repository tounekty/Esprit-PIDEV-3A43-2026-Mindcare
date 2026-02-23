<?php

namespace App\Controller\Admin;

use App\Entity\Commentaire;
use App\Entity\Resource;
use App\Form\ResourceType;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/ressources', name: 'admin_resources_')]
class AdminResourceController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $filters = $this->extractFilters($request);
        $rows = $this->loadResourceRows($em, $filters);
        $stats = $this->buildResourceStats($em);

        return $this->render('admin/gestion_resources/index.html.twig', [
            'rows' => $rows,
            'stats' => $stats,
            'filters' => $filters,
            'visible_count' => count($rows),
        ]);
    }

    #[Route('/ajax', name: 'ajax', methods: ['GET'])]
    public function ajax(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $rows = $this->loadResourceRows($em, $filters);
        $stats = $this->buildResourceStats($em);

        return $this->json([
            'rowsHtml' => $this->renderView('admin/gestion_resources/_rows.html.twig', [
                'rows' => $rows,
            ]),
            'stats' => $stats,
            'visibleCount' => count($rows),
        ]);
    }

    #[Route('/export/pdf', name: 'export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EntityManagerInterface $em): Response
    {
        $filters = $this->extractFilters($request);
        $rows = $this->loadResourceRows($em, $filters);
        $stats = $this->buildResourceStats($em);

        $html = $this->renderView('admin/gestion_resources/export_pdf.html.twig', [
            'rows' => $rows,
            'stats' => $stats,
            'filters' => $filters,
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $fileName = sprintf('resources_export_%s.pdf', (new \DateTimeImmutable())->format('Ymd_His'));

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
            ]
        );
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $resource = new Resource();
        $resource->setUser($this->getUser());
        $form = $this->createForm(ResourceType::class, $resource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resource->normalizeMediaFields();
            $resource->setCreatedAt(new \DateTimeImmutable());
            $em->persist($resource);
            $em->flush();

            $this->addFlash('success', 'Ressource creee avec succes !');
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
            $resource->normalizeMediaFields();
            $em->flush();

            $this->addFlash('success', 'Ressource mise a jour avec succes !');
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
        if ($this->isCsrfTokenValid('delete' . $resource->getId(), $request->request->get('_token'))) {
            $em->remove($resource);
            $em->flush();

            $this->addFlash('success', 'Ressource supprimee avec succes !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide, suppression annulee !');
        }

        return $this->redirectToRoute('admin_resources_index');
    }

    private function extractFilters(Request $request): array
    {
        $query = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', 'all');
        $sort = (string) $request->query->get('sort', 'createdAt');
        $direction = strtoupper((string) $request->query->get('direction', 'DESC'));

        if (!in_array($type, ['all', Resource::TYPE_ARTICLE, Resource::TYPE_VIDEO], true)) {
            $type = 'all';
        }

        if (!in_array($sort, ['id', 'title', 'type', 'createdAt', 'comments'], true)) {
            $sort = 'createdAt';
        }

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }

        return [
            'query' => $query,
            'type' => $type,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    private function loadResourceRows(EntityManagerInterface $em, array $filters): array
    {
        $qb = $em->createQueryBuilder()
            ->select('r', 'COUNT(c.id) AS commentsCount', 'COUNT(c.id) AS HIDDEN commentsCountSort')
            ->from(Resource::class, 'r')
            ->leftJoin('r.commentaires', 'c')
            ->groupBy('r.id');

        if ($filters['query'] !== '') {
            $qb->andWhere('LOWER(r.title) LIKE :q OR LOWER(r.description) LIKE :q')
                ->setParameter('q', '%' . strtolower($filters['query']) . '%');
        }

        if ($filters['type'] !== 'all') {
            $qb->andWhere('r.type = :type')->setParameter('type', $filters['type']);
        }

        $sortMap = [
            'id' => 'r.id',
            'title' => 'r.title',
            'type' => 'r.type',
            'createdAt' => 'r.createdAt',
            'comments' => 'commentsCountSort',
        ];

        $qb->orderBy($sortMap[$filters['sort']], $filters['direction'])
            ->addOrderBy('r.id', 'DESC');

        $result = $qb->getQuery()->getResult();
        $rows = [];

        foreach ($result as $item) {
            if (!is_array($item) || !isset($item[0]) || !$item[0] instanceof Resource) {
                continue;
            }

            $rows[] = [
                'resource' => $item[0],
                'commentsCount' => (int) ($item['commentsCount'] ?? 0),
            ];
        }

        return $rows;
    }

    private function buildResourceStats(EntityManagerInterface $em): array
    {
        $totalResources = (int) $em->getRepository(Resource::class)->count([]);
        $totalVideos = (int) $em->getRepository(Resource::class)->count(['type' => Resource::TYPE_VIDEO]);
        $totalArticles = (int) $em->getRepository(Resource::class)->count(['type' => Resource::TYPE_ARTICLE]);
        $totalComments = (int) $em->getRepository(Commentaire::class)->count([]);

        $averageRatingRaw = $em->createQueryBuilder()
            ->select('AVG(c.rating)')
            ->from(Commentaire::class, 'c')
            ->where('c.rating IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalResources' => $totalResources,
            'totalVideos' => $totalVideos,
            'totalArticles' => $totalArticles,
            'totalComments' => $totalComments,
            'averageRating' => null === $averageRatingRaw ? null : round((float) $averageRatingRaw, 2),
        ];
    }
}
