<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForumController extends AbstractController
{
    #[Route('/forum', name: 'front_forum_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('front/forum/index.html.twig');
    }

    #[Route('/forum/new', name: 'front_forum_new', methods: ['GET'])]
    public function new(): Response
    {
        $form = $this->createFormBuilder()
            ->add('titre', TextType::class)
            ->add('description', TextareaType::class)
            ->add('imageFile', FileType::class, ['required' => false])
            ->add('isPinned', CheckboxType::class, ['required' => false])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'General' => 'general',
                    'Sante mentale' => 'sante_mentale',
                    'Conseils' => 'conseils',
                ],
                'placeholder' => 'Choisir une categorie',
            ])
            ->add('attachmentFile', FileType::class, ['required' => false])
            ->getForm();

        return $this->render('front/forum/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
