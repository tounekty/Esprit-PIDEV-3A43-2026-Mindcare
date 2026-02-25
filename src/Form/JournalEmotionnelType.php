<?php

namespace App\Form;

use App\Entity\JournalEmotionnel;
use App\Entity\Mood;
use App\Repository\MoodRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoodTransformer implements DataTransformerInterface
{
    private $moodRepository;

    public function __construct(MoodRepository $moodRepository)
    {
        $this->moodRepository = $moodRepository;
    }

    public function transform($value)
    {
        if ($value instanceof Mood) {
            return $value->getId();
        }
        return '';
    }

    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }
        $mood = $this->moodRepository->find((int)$value);
        if (!$mood) {
            throw new \Symfony\Component\Form\Exception\TransformationFailedException("Mood not found");
        }
        return $mood;
    }
}

class JournalEmotionnelType extends AbstractType
{
    private $moodRepository;

    public function __construct(MoodRepository $moodRepository)
    {
        $this->moodRepository = $moodRepository;
    }

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
            ->add('mood', HiddenType::class, [
                'label' => 'Humeur associée',
                'required' => false,
            ]);

        // Add data transformer to mood field
        $builder->get('mood')->addModelTransformer(new MoodTransformer($this->moodRepository));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JournalEmotionnel::class,
        ]);
    }
}

