<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
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
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
