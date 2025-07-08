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

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'email',
                TextType::class,
                [
                    'attr' => ['placeholder' => 'E-mail',]
                ]
            )
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Você deve concordar com os nossos termos.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Senha',
                    'class' => 'my-password-field',  // Classe personalizada
                    'data-toggle' => 'password',      // Atributo para JavaScript
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor, insira uma senha',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Sua senha deve ter pelo menos {{ limit }} caracteres',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Confirme a senha',
                    'data-toggle' => 'password',  // Atributo para JavaScript
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor, confirme a senha',
                    ]),
                ],
            ])
            ->add(
                'last_name',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'Sobrenome (apelido(s))',
                    ],
                ]
            )
            ->add(
                'first_name',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'Nome',
                    ],
                ]
            )
            ->add(
                'adress',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'Seu endereço',
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
