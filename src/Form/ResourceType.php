<?php

namespace App\Form;

use App\Entity\Resource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de ressource',
                'required' => false,
                'choices' => [
                    'Article' => Resource::TYPE_ARTICLE,
                    'Video YouTube' => Resource::TYPE_VIDEO,
                ],
                'placeholder' => 'Choisir un type',
            ])
            ->add('videoUrl', TextType::class, [
                'label' => 'Lien YouTube',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://www.youtube.com/watch?v=...',
                ],
            ])
            ->add('imageUrl', TextType::class, [
                'label' => 'Lien image (article)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://exemple.com/image.jpg',
                ],
            ])
            ->add('filePath', TextType::class, [
                'label' => 'Chemin du fichier local (optionnel)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
        ]);
    }
}
