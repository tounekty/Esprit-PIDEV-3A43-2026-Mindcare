<?php

namespace App\Controller;

use App\Entity\MessageForum;
use App\Entity\SujetForum;
use App\Repository\SujetForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontForumController extends AbstractController
{
    #[Route('/forum', name: 'front_forum_index', methods: ['GET'])]
    public function index(SujetForumRepository $repository): Response
    {
        return $this->render('front/forum/index.html.twig', [
            'sujets' => $repository->findBy([], ['dateCreation' => 'DESC']),
        ]);
    }

    #[Route('/forum/new', name: 'front_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sujet = new SujetForum();

        $form = $this->createFormBuilder($sujet)
            ->add('titre', TextType::class, [
                'label' => 'Titre du sujet',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('idUser', IntegerType::class, [
                'label' => 'Votre ID utilisateur',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du sujet',
                'mapped' => false,
                'required' => false,
            ])
            ->add('isPinned', CheckboxType::class, [
                'label' => 'Épingler le sujet',
                'required' => false,
            ])
            ->add('status', TextType::class, [
                'label' => 'Statut',
                'required' => false,
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
            ])
            ->add('attachmentFile', FileType::class, [
                'label' => 'Pièce jointe',
                'mapped' => false,
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleSujetUploads($form, $sujet);
            $entityManager->persist($sujet);
            $entityManager->flush();

            return $this->redirectToRoute('front_forum_index');
        }

        return $this->render('front/forum/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/forum/sujet/{id}', name: 'front_forum_show', methods: ['GET', 'POST'])]
    public function show(Request $request, SujetForum $sujet, EntityManagerInterface $entityManager): Response
    {
        $message = new MessageForum();
        $message->setSujet($sujet);

        $form = $this->createFormBuilder($message)
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre message',
            ])
            ->add('idUser', IntegerType::class, [
                'label' => 'Votre ID utilisateur',
            ])
            ->add('attachmentFile', FileType::class, [
                'label' => 'Pièce jointe',
                'mapped' => false,
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleMessageUploads($form, $message);
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('front_forum_show', ['id' => $sujet->getId()]);
        }

        return $this->render('front/forum/show.html.twig', [
            'sujet' => $sujet,
            'form' => $form->createView(),
        ]);
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
