<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Merchant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;


class MerchantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name',
                'placeholder' => 'Seleciona uma cidade',
                'label' => 'Cidade',
            ])
            ->add('address', TextType::class)
            ->add('description', TextType::class)
            ->add('licenseFile', FileType::class, [
                'label' => 'Licence légale',
                'mapped' => false,
                'required' => false,
            ])
            ->add('bankHolder', TextType::class, [
                'label' => 'Titulaire du compte',
            ])
            ->add('bankName', TextType::class, [
                'label' => 'Nom de la banque',
            ])
            ->add('iban', TextType::class, [
                'label' => 'Número da conta / IBAN',
                'attr' => [
                    'maxlength' => 25,
                    'placeholder' => 'Ex: CV64000500000020108215144',
                    'pattern' => 'CV\\d{2}\\d{21}',
                ],
                'help' => 'O IBAN em Cabo Verde tem 25 caracteres e começa por “CV”, por ex.: CV64000500000020108215144',
                'help_attr' => ['class' => 'form-text text-muted small-text'], // classe personnalisée
            ])
            ->add('swift', TextType::class, [
                'label' => 'Código BIC/SWIFT',
                'attr' => [
                    'maxlength' => 11,
                    'placeholder' => 'Ex: BCVVCVCV',
                    'pattern' => '[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?',
                ],
                'help' => 'O código SWIFT (ou BIC) tem 8 ou 11 caracteres, por ex.: BCVVCVCV',
                'help_attr' => ['class' => 'form-text text-muted small-text'],
            ])->add('nifManeger', TextType::class, [
                    'label' => 'NIF do Comerciante',
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Ex: 123456789',
                        'maxlength' => 9,
                        'class' => 'form-control border',
                    ],
                    'help' => 'O NIF deve ter 9 dígitos e começar por 1, 2, 3 ou 5.',
                    'help_attr' => ['class' => 'form-text text-muted small-text'],
                    'constraints' => [
                        new Length([
                            'min' => 9,
                            'max' => 9,
                            'exactMessage' => 'O NIF deve ter exatamente {{ limit }} dígitos.',
                        ]),
                        new Regex([
                            'pattern' => '/^[1235]\d{8}$/',
                            'message' => 'O NIF deve começar por 1, 2, 3 ou 5 e ter 9 dígitos no total.',
                        ]),
                    ],
                    ]);


    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Merchant::class,
        ]);
    }
}

