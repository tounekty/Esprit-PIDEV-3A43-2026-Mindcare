<?php

namespace App\Form;

use App\Entity\JournalEmotionnel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Mood;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JournalEmotionnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre pensée',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Écrivez vos pensées et émotions...'
                ],
                'row_attr' => ['class' => 'mb-4']
            ])
            ->add('dateecriture', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'input' => 'datetime',
                'html5' => true,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'type' => 'datetime-local'
                ],
                'row_attr' => ['class' => 'mb-4']
            ])
            ->add('mood', EntityType::class, [
                'class' => Mood::class,
                'choice_label' => 'humeur',
                'label' => 'Humeur associée',
                'placeholder' => 'Sélectionnez une humeur',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'row_attr' => ['class' => 'mb-4']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JournalEmotionnel::class,
        ]);
    }
}

