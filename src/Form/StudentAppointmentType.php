<?php

namespace App\Form;

use App\Entity\Appointment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StudentAppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control form-control-lg'],
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
        ]);
    }
}
