<?php

namespace App\Controller\Admin;

use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/commentaires', name: 'admin_comments_')]
class AdminCommentaireController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $filters = $this->extractFilters($request);
        $commentaires = $this->loadComments($em, $filters);
        $stats = $this->buildCommentStats($em);

        return $this->render('admin/gestion_commentaires/index.html.twig', [
            'commentaires' => $commentaires,
            'stats' => $stats,
            'filters' => $filters,
            'visible_count' => count($commentaires),
        ]);
    }

    #[Route('/ajax', name: 'ajax', methods: ['GET'])]
    public function ajax(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $commentaires = $this->loadComments($em, $filters);
        $stats = $this->buildCommentStats($em);

        return $this->json([
            'rowsHtml' => $this->renderView('admin/gestion_commentaires/_rows.html.twig', [
                'commentaires' => $commentaires,
            ]),
            'stats' => $stats,
            'visibleCount' => count($commentaires),
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Commentaire $commentaire, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $request->request->get('_token'))) {
            $em->remove($commentaire);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprime avec succes !');
        }

        return $this->redirectToRoute('admin_comments_index');
    }

    private function extractFilters(Request $request): array
    {
        $query = trim((string) $request->query->get('q', ''));
        $minRating = (int) $request->query->get('minRating', 0);
        $sort = (string) $request->query->get('sort', 'createdAt');
        $direction = strtoupper((string) $request->query->get('direction', 'DESC'));

        if ($minRating < 0 || $minRating > 5) {
            $minRating = 0;
        }

        if (!in_array($sort, ['id', 'resource', 'author', 'rating', 'createdAt'], true)) {
            $sort = 'createdAt';
        }

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }

        return [
            'query' => $query,
            'minRating' => $minRating,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    private function loadComments(EntityManagerInterface $em, array $filters): array
    {
        $qb = $em->createQueryBuilder()
            ->select('c', 'r')
            ->from(Commentaire::class, 'c')
            ->innerJoin('c.resource', 'r');

        if ($filters['query'] !== '') {
            $qb->andWhere('LOWER(c.authorName) LIKE :q OR LOWER(c.authorEmail) LIKE :q OR LOWER(c.content) LIKE :q OR LOWER(r.title) LIKE :q')
                ->setParameter('q', '%' . strtolower($filters['query']) . '%');
        }

        if ($filters['minRating'] > 0) {
            $qb->andWhere('c.rating >= :minRating')->setParameter('minRating', $filters['minRating']);
        }

        $sortMap = [
            'id' => 'c.id',
            'resource' => 'r.title',
            'author' => 'c.authorName',
            'rating' => 'c.rating',
            'createdAt' => 'c.createdAt',
        ];

        $qb->orderBy($sortMap[$filters['sort']], $filters['direction'])
            ->addOrderBy('c.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    private function buildCommentStats(EntityManagerInterface $em): array
    {
        $totalComments = (int) $em->getRepository(Commentaire::class)->count([]);

        $avgRatingRaw = $em->createQueryBuilder()
            ->select('AVG(c.rating)')
            ->from(Commentaire::class, 'c')
            ->where('c.rating IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $todayStart = new \DateTimeImmutable('today');
        $commentsToday = (int) $em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Commentaire::class, 'c')
            ->where('c.createdAt >= :today')
            ->setParameter('today', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $resourcesWithComments = (int) $em->createQueryBuilder()
            ->select('COUNT(DISTINCT IDENTITY(c.resource))')
            ->from(Commentaire::class, 'c')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalComments' => $totalComments,
            'averageRating' => null === $avgRatingRaw ? null : round((float) $avgRatingRaw, 2),
            'commentsToday' => $commentsToday,
            'resourcesWithComments' => $resourcesWithComments,
        ];
    }
}
