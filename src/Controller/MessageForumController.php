<?php

namespace App\Controller;

use App\Entity\MessageForum;
use App\Entity\SujetForum;
use App\Repository\MessageForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MessageForumController extends AbstractController
{
    #[Route('/forum/messages', name: 'message_forum_index', methods: ['GET'])]
    public function index(MessageForumRepository $repository): Response
    {
        return $this->render('forum/message/index.html.twig', [
            'messages' => $repository->findBy([], ['dateMessage' => 'DESC']),
        ]);
    }

    #[Route('/forum/messages/new', name: 'message_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $message = new MessageForum();
        $sujetId = $request->query->get('sujet');
        if ($sujetId) {
            $sujet = $entityManager->getRepository(SujetForum::class)->find($sujetId);
            if ($sujet) {
                $message->setSujet($sujet);
            }
        }

        $form = $this->createFormBuilder($message)
            ->add('sujet', EntityType::class, [
                'class' => SujetForum::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir un sujet',
            ])
            ->add('contenu', TextareaType::class)
            ->add('idUser', IntegerType::class)
            ->add('attachmentFile', FileType::class, ['mapped' => false, 'required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleMessageUploads($form, $message);
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('message_forum_index');
        }

        return $this->render('forum/message/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forum/messages/{id}', name: 'message_forum_show', methods: ['GET'])]
    public function show(MessageForum $message): Response
    {
        return $this->render('forum/message/show.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/forum/messages/{id}/edit', name: 'message_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MessageForum $message, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder($message)
            ->add('sujet', EntityType::class, [
                'class' => SujetForum::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir un sujet',
            ])
            ->add('contenu', TextareaType::class)
            ->add('idUser', IntegerType::class)
            ->add('attachmentFile', FileType::class, ['mapped' => false, 'required' => false])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleMessageUploads($form, $message);
            $entityManager->flush();

            return $this->redirectToRoute('message_forum_index');
        }

        return $this->render('forum/message/edit.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
        ]);
    }

    #[Route('/forum/messages/{id}/delete', name: 'message_forum_delete', methods: ['POST'])]
    public function delete(Request $request, MessageForum $message, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_message_' . $message->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($message);
            $entityManager->flush();
        }

        return $this->redirectToRoute('message_forum_index');
    }

    private function handleMessageUploads($form, MessageForum $message): void
    {
        $uploadRoot = rtrim($this->getParameter('uploads_dir'), DIRECTORY_SEPARATOR);

        /** @var UploadedFile|null $attachmentFile */
        $attachmentFile = $form->get('attachmentFile')->getData();
        if ($attachmentFile instanceof UploadedFile) {
            $attachDir = $uploadRoot . DIRECTORY_SEPARATOR . 'message-attachments';
            if (!is_dir($attachDir)) {
                mkdir($attachDir, 0775, true);
            }

            $filename = uniqid('message_att_', true) . '.' . $attachmentFile->guessExtension();
            try {
                $attachmentFile->move($attachDir, $filename);
                $message->setAttachmentPath('/uploads/message-attachments/' . $filename);
                $message->setAttachmentMimeType($attachmentFile->getMimeType());
                $message->setAttachmentSize($attachmentFile->getSize());
            } catch (\Exception $e) {
                // Skip upload on error - file will not be saved
            }
        }
    }
}
