<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\EqualTo;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'email',
                TextType::class,
                [
                    'attr' => ['placeholder' => 'form.email_placeholder',],
                ]
            )
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'form.agree_terms_message',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'form.password_placeholder',
                    'class' => 'my-password-field',  
                    'data-toggle' => 'password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.password_blank_message',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'form.password_length_message',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'form.confirm_password_placeholder',
                    'data-toggle' => 'password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.confirm_password_blank_message',
                    ]),
                    new EqualTo([
                        'value' => 'plainPassword',
                        'message' => 'Les mots de passe doivent correspondre.',
                    ]),
                ],
            ])
            ->add(
                'last_name',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'form.last_name_placeholder',
                    ],
                ]
            )
            ->add(
                'first_name',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'form.first_name_placeholder',
                    ],
                ]
            )
            ->add(
                'adress',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'form.address_placeholder',
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
