<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Mood;

class MoodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('humeur', ChoiceType::class, [
                'label' => 'Comment vous sentez-vous ?',
                'choices' => [
                    '😄 Heureux(euse)' => 'heureux',
                    '😐 Neutre' => 'neutre',
                    '😢 Triste' => 'triste',
                    '😠 En colère' => 'colere',
                    '😰 Stressé(e)' => 'stresse',
                    '😌 Calme' => 'calme',
                    '😴 Fatigué(e)' => 'fatigue',
                    '😃 Excité(e)' => 'excite'
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => ['class' => 'fw-bold'],
                'attr' => ['class' => 'mood-choices'],
                'row_attr' => ['class' => 'mb-4']
            ])
            ->add('intensite', RangeType::class, [
                'label' => 'Intensité : <span id="intensityValue">3</span>/5',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-range intensity-slider'
                ],
                'row_attr' => ['class' => 'mb-4']
            ])
            ->add('datemood', \Symfony\Component\Form\Extension\Core\Type\DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control datepicker'
                ],
                'row_attr' => ['class' => 'mb-4']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mood::class,
            'attr' => ['class' => 'mood-form']
        ]);
    }
}