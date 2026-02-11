<?php

namespace App\Form;

use App\Entity\PatientFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FullPatientFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isAdmin = $options['is_admin'];

        $builder
            // Student information (Editable by Psy and Admin)
            ->add('traitementsEnCours', TextareaType::class, [
                'label' => 'Traitements en cours',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ])
            ->add('allergies', TextareaType::class, [
                'label' => 'Allergies',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2]
            ])
            ->add('contactUrgenceNom', TextType::class, [
                'label' => 'Nom du contact d\'urgence',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('contactUrgenceTel', TextType::class, [
                'label' => 'Téléphone du contact d\'urgence',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            // Clinical Information (Only Psy can edit, Admin see-only)
            ->add('antecedentsPersonnels', TextareaType::class, [
                'label' => 'Antécédents Personnels',
                'required' => false,
                'disabled' => $isAdmin,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('antecedentsFamiliaux', TextareaType::class, [
                'label' => 'Antécédents Familiaux',
                'required' => false,
                'disabled' => $isAdmin,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('motifConsultation', TextareaType::class, [
                'label' => 'Motif de la consultation',
                'required' => false,
                'disabled' => $isAdmin,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('objectifsTherapeutiques', TextareaType::class, [
                'label' => 'Objectifs Thérapeutiques',
                'required' => false,
                'disabled' => $isAdmin,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('notesGenerales', TextareaType::class, [
                'label' => 'Notes Générales (Confidentiel)',
                'required' => false,
                'disabled' => $isAdmin,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('niveauRisque', ChoiceType::class, [
                'label' => 'Niveau de Risque',
                'disabled' => $isAdmin,
                'choices' => [
                    'Faible' => 'Low',
                    'Moyen' => 'Medium',
                    'Élevé' => 'High',
                ],
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PatientFile::class,
            'is_admin' => false,
        ]);
    }
}
