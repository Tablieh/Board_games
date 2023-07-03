<?php

namespace App\Form;

use App\Entity\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('choix', ChoiceType::class, [
                    'choices' => [
                        'Yes' => true,
                        'No' => false,
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'attr' => [
                        'class' => 'choix',
                        'onclick' => 'changeColorButton(event)', // Pass event object
                    ]
                ])

            /* ->add('no', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-checkbox',
                    'onclick' => 'changeColorButton(this)',
                ],
                'label' => 'No',
            ]) */
            ->add('name', TextType::class, [
                'label' => 'Your name',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your name',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Your name should be less than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your email',
                    ]),
                    new Email([
                        'message' => 'The email "{{ value }}" is not a valid email',
                    ]),
                ],
            ])
            ->add('issueName', TextType::class, [
                'label' => 'The issues Name',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'The issue name should be less than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('issueDescription', TextareaType::class, [
                'label' => 'Describe the issue',
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter the issue description',
                    ]),
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'The issue description should be less than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}
