<?php

namespace App\Form;

use App\Entity\Appointment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(['message' => 'The date is required.']),
                    new Assert\Callback(function($value, $context) {
                        if ($value === null) {
                            return;
                        }
                        
                        $now = new \DateTime();
                        $minDate = $now->modify('+1 hour');
                        
                        if ($value < $minDate) {
                            $context->buildViolation('The appointment date must be at least 1 hour from now.')
                                ->addViolation();
                        }
                    })
                ],
            ])
            ->add('location', ChoiceType::class, [
                'choices' => [
                    'En cabinet' => 'in_office',
                    'En ligne' => 'online',
                ],
                'placeholder' => $builder->getData()->getId() ? null : 'Choisir type',
                'attr' => ['class' => 'form-control'],
                'label' => 'Lieu du rendez-vous',
                'required' => true,
            ])
            ->add('etudiant', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (UserRepository $ur) {
                    return $ur->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', 'etudiant')
                        ->orderBy('u.lastName', 'ASC');
                },
                'choice_label' => function (User $u) { return $u->getFirstName() . ' ' . $u->getLastName(); },
                'placeholder' => 'Choisir un étudiant',
                'required' => true,
                'disabled' => $options['is_psychologue'] ?? false,
            ])
            ->add('psychologue', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (UserRepository $ur) {
                    return $ur->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', 'psychologue')
                        ->orderBy('u.lastName', 'ASC');
                },
                'choice_label' => function (User $u) { return $u->getFirstName() . ' ' . $u->getLastName(); },
                'placeholder' => 'Choisir un psychologue',
                'required' => true,
                'disabled' => $options['is_psychologue'] ?? false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'is_psychologue' => false,
        ]);
    }
}
