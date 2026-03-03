<?php

namespace App\Form;

use App\Entity\PatientFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentPatientFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('traitementsEnCours', TextareaType::class, [
                'label' => 'Traitements en cours',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Listez vos médicaments ou traitements actuels...']
            ])
            ->add('allergies', TextareaType::class, [
                'label' => 'Allergies',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Précisez vos allergies connues...']
            ])
            ->add('contactUrgenceNom', TextType::class, [
                'label' => 'Nom du contact d\'urgence',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom et Prénom']
            ])
            ->add('contactUrgenceTel', TextType::class, [
                'label' => 'Téléphone du contact d\'urgence',
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 06 00 00 00 00']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PatientFile::class,
        ]);
    }
}
