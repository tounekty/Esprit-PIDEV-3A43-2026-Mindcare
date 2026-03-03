<?php

namespace App\Controller\Admin;

use App\Entity\Commentaire;
use App\Entity\Resource;
use App\Form\ResourceType;
use App\Service\GoogleAnalyticsResourceMetricsService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

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

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(
        EntityManagerInterface $em,
        ChartBuilderInterface $chartBuilder,
        GoogleAnalyticsResourceMetricsService $gaMetricsService
    ): Response
    {
        $rows = $em->createQueryBuilder()
            ->select('r.title AS title', 'COUNT(c.id) AS commentsCount', 'AVG(c.rating) AS averageRating')
            ->from(Resource::class, 'r')
            ->leftJoin('r.commentaires', 'c')
            ->groupBy('r.id')
            ->orderBy('commentsCount', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getArrayResult();

        $labels = [];
        $commentsData = [];
        $ratingsData = [];

        foreach ($rows as $row) {
            $title = (string) ($row['title'] ?? '');
            $labels[] = mb_strlen($title) > 22 ? mb_substr($title, 0, 22) . '...' : $title;
            $commentsData[] = (int) ($row['commentsCount'] ?? 0);
            $ratingsData[] = round((float) ($row['averageRating'] ?? 0), 2);
        }

        if ([] === $labels) {
            $labels = ['Aucune ressource'];
            $commentsData = [0];
            $ratingsData = [0];
        }

        $commentsChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $commentsChart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre de commentaires',
                    'data' => $commentsData,
                    'backgroundColor' => 'rgba(13, 110, 253, 0.6)',
                    'borderColor' => 'rgba(13, 110, 253, 1)',
                    'borderWidth' => 1,
                ],
            ],
        ]);
        $commentsChart->setOptions([
            'responsive' => true,
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ]);

        $ratingsChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $ratingsChart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Note moyenne (/5)',
                    'data' => $ratingsData,
                    'fill' => false,
                    'borderColor' => 'rgba(25, 135, 84, 1)',
                    'backgroundColor' => 'rgba(25, 135, 84, 0.35)',
                    'tension' => 0.3,
                ],
            ],
        ]);
        $ratingsChart->setOptions([
            'responsive' => true,
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 5,
                ],
            ],
        ]);

        $gaAnalytics = $gaMetricsService->getResourceMetrics();
        $gaViewsChart = null;
        $gaCategoriesChart = null;

        if (($gaAnalytics['available'] ?? false) && ($gaAnalytics['topResources'] ?? []) !== []) {
            $gaViewLabels = [];
            $gaViewData = [];

            foreach ($gaAnalytics['topResources'] as $item) {
                $title = (string) ($item['title'] ?? '');
                $gaViewLabels[] = mb_strlen($title) > 22 ? mb_substr($title, 0, 22) . '...' : $title;
                $gaViewData[] = (int) ($item['views'] ?? 0);
            }

            $gaViewsChart = $chartBuilder->createChart(Chart::TYPE_BAR);
            $gaViewsChart->setData([
                'labels' => $gaViewLabels,
                'datasets' => [
                    [
                        'label' => 'Vues (Google Analytics)',
                        'data' => $gaViewData,
                        'backgroundColor' => 'rgba(255, 159, 64, 0.7)',
                        'borderColor' => 'rgba(255, 159, 64, 1)',
                        'borderWidth' => 1,
                    ],
                ],
            ]);
            $gaViewsChart->setOptions([
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => true],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'precision' => 0,
                        ],
                    ],
                ],
            ]);
        }

        if (($gaAnalytics['available'] ?? false) && ($gaAnalytics['topCategories'] ?? []) !== []) {
            $categoryLabels = [];
            $categoryData = [];
            foreach ($gaAnalytics['topCategories'] as $item) {
                $categoryLabels[] = (string) ($item['label'] ?? 'Autre');
                $categoryData[] = (int) ($item['views'] ?? 0);
            }

            $palette = [
                'rgba(13, 110, 253, 0.78)',
                'rgba(25, 135, 84, 0.78)',
                'rgba(255, 193, 7, 0.78)',
                'rgba(220, 53, 69, 0.78)',
                'rgba(111, 66, 193, 0.78)',
            ];
            $backgroundColors = [];
            $count = count($categoryData);
            for ($i = 0; $i < $count; $i++) {
                $backgroundColors[] = $palette[$i % count($palette)];
            }

            $gaCategoriesChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
            $gaCategoriesChart->setData([
                'labels' => $categoryLabels,
                'datasets' => [
                    [
                        'label' => 'Vues par categorie',
                        'data' => $categoryData,
                        'backgroundColor' => $backgroundColors,
                        'borderWidth' => 1,
                    ],
                ],
            ]);
            $gaCategoriesChart->setOptions([
                'responsive' => true,
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
            ]);
        }

        return $this->render('admin/gestion_resources/stats.html.twig', [
            'commentsChart' => $commentsChart,
            'ratingsChart' => $ratingsChart,
            'ga_analytics' => $gaAnalytics,
            'ga_views_chart' => $gaViewsChart,
            'ga_categories_chart' => $gaCategoriesChart,
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

    #[Route('/generate-image', name: 'generate_image', methods: ['POST'])]
    public function generateImage(Request $request, HttpClientInterface $httpClient, string $groqApiKey, string $hfApiToken): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $prompt = trim((string) ($data['prompt'] ?? ''));

        if ($prompt === '') {
            return $this->json(['error' => 'Prompt vide.'], 400);
        }

        // Step 1 — Groq: translate + enhance the prompt to English
        $englishPrompt = $prompt;
        try {
            $groqResp = $httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'llama-3.3-70b-versatile',
                    'messages'    => [
                        ['role' => 'system', 'content' => 'You are an expert image prompt writer. Translate the user description (possibly in French) to English and enrich it with vivid details: lighting, style, mood, colors, composition. Return ONLY the enhanced English prompt, no quotes, max 80 words.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'max_tokens'  => 120,
                    'temperature' => 0.7,
                ],
                'timeout' => 10,
            ]);
            $enhanced = trim((string) ($groqResp->toArray(false)['choices'][0]['message']['content'] ?? ''));
            if ($enhanced !== '') {
                $englishPrompt = $enhanced;
            }
        } catch (\Throwable) {
            // keep original prompt
        }

        // Step 2 — Hugging Face Router API (FLUX.1-schnell) — works server-side
        $hfUrl = 'https://router.huggingface.co/hf-inference/models/black-forest-labs/FLUX.1-schnell';
        try {
            $hfResp = $httpClient->request('POST', $hfUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $hfApiToken,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'image/jpeg',
                ],
                'json'    => ['inputs' => $englishPrompt],
                'timeout' => 120,
            ]);

            $status = $hfResp->getStatusCode();

            if ($status === 503) {
                // Model is loading — retry once after 20s
                sleep(20);
                $hfResp = $httpClient->request('POST', $hfUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $hfApiToken,
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'image/jpeg',
                    ],
                    'json'    => ['inputs' => $englishPrompt],
                    'timeout' => 120,
                ]);
                $status = $hfResp->getStatusCode();
            }

            if ($status !== 200) {
                $body = $hfResp->getContent(false);
                $msg  = json_decode($body, true)['error'] ?? ('Erreur service image (HTTP ' . $status . ')');
                return $this->json(['error' => $msg . '. Vérifiez votre HF_API_TOKEN.'], 502);
            }

            // Save the image to public/uploads/resources/
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/resources';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }
            $fileName = 'ai_' . uniqid() . '.jpg';
            file_put_contents($uploadsDir . '/' . $fileName, $hfResp->getContent());

            return $this->json([
                'url'            => '/uploads/resources/' . $fileName,
                'enhancedPrompt' => $englishPrompt,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Timeout ou service indisponible. Réessayez.'], 504);
        }
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
