<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomEtudiant', null, [
                'label' => 'Nom complet de l\'étudiant',
                'attr' => ['placeholder' => 'Jean Dupont']
            ])
            ->add('emailEtudiant', null, [
                'label' => 'Email institutionnel',
                'attr' => ['placeholder' => 'jean@etudiant.tn']
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => function (Event $event) {
                    return $event->getTitre() . ' - ' . $event->getLieu() . ' (' . $event->getDateHeure()->format('d/m/Y H:i') . ')';
                },
                'label' => 'Sélectionner l\'atelier',
                'placeholder' => '-- Choisir un atelier --',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}