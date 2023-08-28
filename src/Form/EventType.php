<?php

namespace App\Form;

use App\Entity\Event;
use NumberFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('date_event' ,DateTimeType::class, [
                    /* 'placeholder' => [
                        'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
                        'hour' => 'Hour', 'minute' => 'Minute', 'second' => 'Second',
                    ], */
                    'widget' => 'single_text'
                ])
                
            ->add('places', IntegerType::class)
            ->add('description', TextareaType::class)
            ->add('adresse', TextType::class)
            ->add('cp', TextType::class)
            ->add('city', TextType::class)
            /* ->add('images', TextareaType::class) */
            ->add('imageFile', VichImageType::class, [
                'label' => 'Images upload',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '9200k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            // add more valid MIME types here if needed
                        ],
                        'mimeTypesMessage' => 'Please upload a valid Images document',
                    ])
                ],
            ])
            ->add('id_game', HiddenType::class, [
                    'data' => null, // this can be null or a default value
                ])
            ->add('lat', HiddenType::class, [
                        'label' => 'lat',
            ])
            ->add('lon', HiddenType::class, [
                        'label' => 'lon',
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
            'data_class' => Event::class
        ]);
    }
}
