<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Resource;
use App\Entity\User;
use App\Form\CommentaireType;
use App\Repository\ResourceRepository;
use App\Service\OpenAiModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResourceController extends AbstractController
{
    #[Route('/resources', name: 'resource_index', methods: ['GET'])]
    public function index(Request $request, ResourceRepository $resourceRepository, PaginatorInterface $paginator): Response
    {
        $query = trim((string) $request->query->get('q', ''));

        $qb = $resourceRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');

        if ($query !== '') {
            $qb->andWhere('LOWER(r.title) LIKE :q OR LOWER(r.description) LIKE :q')
                ->setParameter('q', '%' . strtolower($query) . '%');
        }

        $resources = $paginator->paginate(
            $qb,
            max(1, (int) $request->query->get('page', 1)),
            6
        );

        return $this->render('resource/index.html.twig', [
            'resources' => $resources,
            'q' => $query,
        ]);
    }

    #[Route('/resources/{id}', name: 'resource_show', methods: ['GET', 'POST'])]
    public function show(
        Resource $resource,
        Request $request,
        EntityManagerInterface $entityManager,
        ResourceRepository $resourceRepository,
        OpenAiModerationService $moderationService
    ): Response {
        $commentForm = null;
        $user = $this->getUser();

        if ($user instanceof User) {
            $commentaire = new Commentaire();
            $commentaire->setResource($resource);
            $commentaire->setUser($user);
            $commentaire->setAuthorName(trim(sprintf('%s %s', (string) $user->getFirstName(), (string) $user->getLastName())));
            $commentaire->setAuthorEmail((string) $user->getEmail());

            $form = $this->createForm(CommentaireType::class, $commentaire);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Enforce author data from authenticated user (hidden fields are client-side).
                $commentaire->setResource($resource);
                $commentaire->setUser($user);
                $commentaire->setAuthorName(trim(sprintf('%s %s', (string) $user->getFirstName(), (string) $user->getLastName())));
                $commentaire->setAuthorEmail((string) $user->getEmail());
                $moderation = $moderationService->moderate($commentaire->getContent() ?? '');
                $errorType = $moderation['errorType'] ?? null;
                $errorMessage = $moderation['errorMessage'] ?? null;
                $fallbackUsed = (bool) ($moderation['fallbackUsed'] ?? false);
                $detailSuffix = is_string($errorMessage) && $errorMessage !== '' ? ' Detail: ' . $errorMessage . '.' : '';

                if (!$moderation['enabled']) {
                    $form->addError(new FormError('Moderation OpenAI non configuree. Ajoutez OPENAI_API_KEY dans .env.local.'));
                } elseif (!$moderation['checked']) {
                    if ($errorType === 'rate_limit') {
                        $form->addError(new FormError('OpenAI refuse la verification (429). Verifiez votre quota/facturation et les limites du projet sur platform.openai.com, puis reessayez.' . $detailSuffix));
                    } elseif ($errorType === 'auth') {
                        $form->addError(new FormError('Cle OpenAI invalide ou sans droits. Verifiez OPENAI_API_KEY dans .env.local.' . $detailSuffix));
                    } elseif ($errorType === 'provider') {
                        $form->addError(new FormError('Service OpenAI indisponible temporairement. Reessayez plus tard.' . $detailSuffix));
                    } else {
                        $form->addError(new FormError('Impossible de verifier le commentaire avec OpenAI pour le moment. Reessayez plus tard.' . $detailSuffix));
                    }
                } elseif ($moderation['flagged']) {
                    $categories = $moderation['categories'] ?? [];
                    $details = $categories !== [] ? ' Categories detectees: ' . implode(', ', $categories) . '.' : '';

                    $form->get('content')->addError(new FormError('Commentaire refuse: contenu toxique ou spam detecte.' . $details));
                    $this->addFlash('danger', 'Commentaire refuse: contenu toxique ou spam detecte.');
                } else {
                    $commentaire->setApproved(true);

                    $entityManager->persist($commentaire);
                    $entityManager->flush();

                    if ($fallbackUsed) {
                        $this->addFlash('warning', 'OpenAI indisponible: publication validee par filtre local.');
                    }

                    $this->addFlash('success', 'Commentaire ajoute avec succes.');

                    return $this->redirectToRoute('resource_show', ['id' => $resource->getId()]);
                }
            }

            $commentForm = $form->createView();
        }

        return $this->render('resource/show.html.twig', [
            'resource' => $resource,
            'all_resources' => $resourceRepository->findBy([], ['createdAt' => 'DESC']),
            'commentForm' => $commentForm,
        ]);
    }
}
