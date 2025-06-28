<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Regex;

class ChangePasswordFormType extends AbstractType
{
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'options' => [
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ],
            'first_options' => [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Por favor, digite uma senha.',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'A senha deve ter no mínimo {{ limit }} caracteres.',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[A-Z])(?=.*[\W_]).+$/',
                        'message' => 'A senha deve conter no mínimo 8 caracteres, uma letra maiúscula e um caractere especial (ex: @, &, #, ...).',
                    ]),
                ],
                'label' => 'Nova senha',
                'attr' => [
                    'id' => 'new_password',
                ],
            ],
            'second_options' => [
                'label' => 'Repita a nova senha',
                'attr' => [
                    'id' => 'repeat_password',
                ],
            ],
            'invalid_message' => 'As senhas não coincidem.',
            'mapped' => false,
        ]);
}


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
