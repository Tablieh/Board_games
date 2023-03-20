<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextareaType::class)
            ->add('date_event' ,DateTimeType::class, [
                    'placeholder' => [
                    'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
                    'hour' => 'Hour', 'minute' => 'Minute', 'second' => 'Second',
                ],
                ])
            ->add('places', IntegerType::class)
            ->add('description', TextareaType::class)
            ->add('adresse', TextareaType::class)
            ->add('cp', TextareaType::class)
            ->add('city', TextareaType::class)
            ->add('images', TextareaType::class)
            ->add('id_game', HiddenType::class, [
                    'data' => null, // this can be null or a default value
                ])

            ->add('Valider', SubmitType::class, [
                'attr' => [
                    "class" => "uk-button uk-button-primary uk-button-small",
                ]
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
