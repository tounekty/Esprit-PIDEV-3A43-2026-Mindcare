<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
<<<<<<< HEAD
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('dateHeure', null, [
                'widget' => 'single_text',
            ])
            ->add('lieu')
            ->add('capaciteMax')
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'required' => false,
                'scale' => 8,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Ex: 33.7931611111',
                    'step' => 'any',
                    'min' => '-90',
                    'max' => '90',
                ]
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'required' => false,
                'scale' => 8,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Ex: 10.1619222222',
                    'step' => 'any',
                    'min' => '-180',
                    'max' => '180',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
=======
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('dateEvent', DateTimeType::class, [
                'label' => 'Date de l\'evenement',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'La date est obligatoire.']),
                    new Assert\GreaterThan('now', message: 'La date doit etre dans le futur.'),
                ],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacite',
                'attr' => ['class' => 'form-control', 'min' => 1],
                'constraints' => [
                    new Assert\NotNull(['message' => 'La capacite est obligatoire.']),
                    new Assert\Positive(['message' => 'La capacite doit etre positive.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
