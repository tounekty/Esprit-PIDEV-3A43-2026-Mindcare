<?php

namespace App\Form;

use App\Entity\Appointment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichFileType;

class AppointmentReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('reportFile', VichFileType::class, [
            'required' => false,
            'label' => 'Fichier du compte rendu',
            'label_attr' => ['class' => 'form-label'],
            'attr' => ['class' => 'form-control'],
            'help' => 'PDF, PNG ou JPG (max 5MB)',
            'help_attr' => ['class' => 'form-text text-muted d-block mt-2'],
            'allow_delete' => true,
            'download_uri' => false,
            'constraints' => [
                new Assert\File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                    ],
                    'mimeTypesMessage' => 'Veuillez televerser un PDF, PNG ou JPG valide.',
                    'groups' => ['file_upload'],
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
        ]);
    }
}
