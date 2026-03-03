<?php

namespace App\Controller;

use App\Entity\SujetForum;
use App\Repository\SujetForumRepository;
use App\Service\OpenAiModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SujetForumController extends AbstractController
{
    #[Route('/forum/sujets', name: 'sujet_forum_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, SujetForumRepository $repository, PaginatorInterface $paginator): Response
    {
        $filters = $this->extractFilters($request);
        $pagination = $paginator->paginate(
            $repository->createFilteredQueryBuilder(
                $filters['query'] !== '' ? $filters['query'] : null,
                $filters['status'],
                $filters['sort'],
                $filters['direction']
            ),
            max(1, (int) $request->query->get('page', 1)),
            5
        );
        $stats = $this->buildSujetStats($em);

        return $this->render('forum/sujet/index.html.twig', [
            'sujets' => $pagination,
            'stats' => $stats,
            'filters' => $filters,
            'visible_count' => count($pagination),
            'statusChoices' => SujetForum::getStatusChoices(),
        ]);
    }

    #[Route('/forum/sujets/ajax', name: 'sujet_forum_ajax', methods: ['GET'])]
    public function ajax(Request $request, EntityManagerInterface $em, SujetForumRepository $repository, PaginatorInterface $paginator): Response
    {
        $filters = $this->extractFilters($request);
        $pagination = $paginator->paginate(
            $repository->createFilteredQueryBuilder(
                $filters['query'] !== '' ? $filters['query'] : null,
                $filters['status'],
                $filters['sort'],
                $filters['direction']
            ),
            max(1, (int) $request->query->get('page', 1)),
            5
        );
        $stats = $this->buildSujetStats($em);

        return $this->json([
            'rowsHtml' => $this->renderView('forum/sujet/_rows.html.twig', [
                'sujets' => $pagination,
            ]),
            'stats' => $stats,
            'visibleCount' => count($pagination),
        ]);
    }

    #[Route('/forum/sujets/new', name: 'sujet_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, OpenAiModerationService $moderationService): Response
    {
       $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $sujet = new SujetForum();
        $sujet->setStatus(SujetForum::STATUS_VISIBLE);
        $sujet->setUser($this->getUser());
        $form = $this->createFormBuilder($sujet)
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class)
            ->add('isAnonymous', ChoiceType::class, [
                'label' => 'Publication anonyme',
                'choices' => [
                    'Normal' => false,
                    'Anonyme' => true,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('imageFile', FileType::class, ['mapped' => false, 'required' => false])
            ->add('isPinned', CheckboxType::class, ['required' => false])
            ->add('status', ChoiceType::class, [
                'choices' => SujetForum::getStatusChoices(),
                'placeholder' => 'Aucun statut',
                'required' => false,
            ])
            ->add('category', TextType::class, ['required' => false])
            ->add('attachmentFile', FileType::class, ['mapped' => false, 'required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $moderation = $moderationService->moderate($sujet->getDescription() ?? '');
            $errorType = $moderation['errorType'] ?? null;
            $errorMessage = $moderation['errorMessage'] ?? null;
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

                $form->get('description')->addError(new FormError('Commentaire refuse: contenu toxique ou spam detecte.' . $details));
                $this->addFlash('danger', 'Commentaire refuse: contenu toxique ou spam detecte.');
            } else {
                $this->handleSujetUploads($form, $sujet);
                $entityManager->persist($sujet);
                $entityManager->flush();

                return $this->redirectToRoute('sujet_forum_index');
            }
        }

        return $this->render('forum/sujet/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forum/sujets/{id}', name: 'sujet_forum_show', methods: ['GET'])]
    public function show(SujetForum $sujet): Response
    {
        return $this->render('forum/sujet/show.html.twig', [
            'sujet' => $sujet,
        ]);
    }

    #[Route('/forum/sujets/{id}/edit', name: 'sujet_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SujetForum $sujet, EntityManagerInterface $entityManager, OpenAiModerationService $moderationService): Response
    {
        $form = $this->createFormBuilder($sujet)
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class)
            ->add('isAnonymous', ChoiceType::class, [
                'label' => 'Publication anonyme',
                'choices' => [
                    'Normal' => false,
                    'Anonyme' => true,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('imageFile', FileType::class, ['mapped' => false, 'required' => false])
            ->add('isPinned', CheckboxType::class, ['required' => false])
            ->add('status', ChoiceType::class, [
                'choices' => SujetForum::getStatusChoices(),
                'placeholder' => 'Aucun statut',
                'required' => false,
            ])
            ->add('category', TextType::class, ['required' => false])
            ->add('attachmentFile', FileType::class, ['mapped' => false, 'required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $moderation = $moderationService->moderate($sujet->getDescription() ?? '');
            $errorType = $moderation['errorType'] ?? null;
            $errorMessage = $moderation['errorMessage'] ?? null;
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

                $form->get('description')->addError(new FormError('Commentaire refuse: contenu toxique ou spam detecte.' . $details));
                $this->addFlash('danger', 'Commentaire refuse: contenu toxique ou spam detecte.');
            } else {
                $this->handleSujetUploads($form, $sujet);
                $entityManager->flush();

                return $this->redirectToRoute('sujet_forum_index');
            }
        }

        return $this->render('forum/sujet/edit.html.twig', [
            'form' => $form->createView(),
            'sujet' => $sujet,
        ]);
    }

    #[Route('/forum/sujets/{id}/delete', name: 'sujet_forum_delete', methods: ['POST'])]
    public function delete(Request $request, SujetForum $sujet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_sujet_' . $sujet->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($sujet);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sujet_forum_index');
    }

    private function handleSujetUploads($form, SujetForum $sujet): void
    {
        $uploadRoot = rtrim($this->getParameter('uploads_dir'), DIRECTORY_SEPARATOR);

        /** @var UploadedFile|null $imageFile */
        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile instanceof UploadedFile) {
            $imageDir = $uploadRoot . DIRECTORY_SEPARATOR . 'sujet-images';
            if (!is_dir($imageDir)) {
                mkdir($imageDir, 0775, true);
            }

            $filename = uniqid('sujet_img_', true) . '.' . $imageFile->guessExtension();
            try {
                $imageFile->move($imageDir, $filename);
                $sujet->setImageUrl('/uploads/sujet-images/' . $filename);
            } catch (\Exception $e) {
                // Skip upload on error - file will not be saved
            }
        }

        /** @var UploadedFile|null $attachmentFile */
        $attachmentFile = $form->get('attachmentFile')->getData();
        if ($attachmentFile instanceof UploadedFile) {
            $attachDir = $uploadRoot . DIRECTORY_SEPARATOR . 'sujet-attachments';
            if (!is_dir($attachDir)) {
                mkdir($attachDir, 0775, true);
            }

            $filename = uniqid('sujet_att_', true) . '.' . $attachmentFile->guessExtension();
            try {
                $attachmentFile->move($attachDir, $filename);
                $sujet->setAttachmentPath('/uploads/sujet-attachments/' . $filename);
                $sujet->setAttachmentMimeType($attachmentFile->getMimeType());
                $sujet->setAttachmentSize($attachmentFile->getSize());
            } catch (\Exception $e) {
                // Skip upload on error - file will not be saved
            }
        }
    }

    private function extractFilters(Request $request): array
    {
        $query = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', 'all');
        $sort = (string) $request->query->get('sort', 'date');
        $direction = strtoupper((string) $request->query->get('direction', 'DESC'));

        $validStatuses = array_merge(['all'], SujetForum::getStatusValues());
        if (!in_array($status, $validStatuses, true)) {
            $status = 'all';
        }

        if (!in_array($sort, ['date', 'title', 'status'], true)) {
            $sort = 'date';
        }

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }

        return [
            'query' => $query,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    private function buildSujetStats(EntityManagerInterface $em): array
    {
        $total = (int) $em->getRepository(SujetForum::class)->count([]);
        $pinned = (int) $em->getRepository(SujetForum::class)->count(['isPinned' => true]);

        $countsByStatus = [];
        foreach (SujetForum::getStatusValues() as $status) {
            $countsByStatus[$status] = (int) $em->getRepository(SujetForum::class)->count(['status' => $status]);
        }

        return [
            'total' => $total,
            'pinned' => $pinned,
            'byStatus' => $countsByStatus,
        ];
    }
}
