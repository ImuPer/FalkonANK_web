<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('email', TextType::class, [
                'attr' => [
                    'placeholder' => 'register.form.email',
                ],
                'label' => false,
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'register.form.agree_terms_error',
                    ]),
                ],
            ])

            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'register.form.password',
                    'class' => 'my-password-field',
                    'data-toggle' => 'password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'register.form.password_blank',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'register.form.password_min',
                        'max' => 4096,
                    ]),
                ],
            ])

            ->add('confirmPassword', PasswordType::class, [
                'mapped' => false,
                'label' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'register.form.password_confirm',
                    'data-toggle' => 'password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'register.form.password_confirm_blank',
                    ]),
                ],
            ])

            ->add('first_name', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'register.form.first_name',
                ],
            ])

            ->add('last_name', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'register.form.last_name',
                ],
            ])

            ->add('adress', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'register.form.address',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'messages',
        ]);
    }
}
