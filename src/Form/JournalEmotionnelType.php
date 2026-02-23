<?php

namespace App\Form;

use App\Entity\JournalEmotionnel;
use App\Entity\Mood;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JournalEmotionnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre pensée',
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
                'format' => 'yyyy-MM-dd HH:mm',
                'input' => 'datetime',
                'html5' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'row_attr' => ['class' => 'mb-4'],
                'empty_data' => function() {
                    return new \DateTime();
                }
            ])
            ->add('mood', EntityType::class, [
                'label' => 'Humeur associée',
                'class' => Mood::class,
                'choice_label' => function(Mood $mood) {
                    return ucfirst($mood->getHumeur());
                },
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

