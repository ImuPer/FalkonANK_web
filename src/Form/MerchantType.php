<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Merchant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            ->add('name', TextType::class, [
                'label' => 'merchant.name',
            ])

            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name',
                'placeholder' => 'merchant.city_placeholder',
                'label' => 'merchant.city',
            ])

            ->add('address', TextType::class, [
                'label' => 'merchant.address',
            ])

            ->add('description', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control mb-3',
                    'rows' => 5,
                    'placeholder' => 'merchant.description'
                ],
            ])
            
            ->add('licenseFile', FileType::class, [
                'label' => 'merchant.license',
                'mapped' => false,
                'required' => true,
            ])

            ->add('bankHolder', TextType::class, [
                'label' => 'merchant.bank_holder',
            ])

            ->add('bankName', TextType::class, [
                'label' => 'merchant.bank_name',
            ])

            ->add('iban', TextType::class, [
                'label' => 'merchant.iban',
                'attr' => [
                    'maxlength' => 25,
                    'placeholder' => 'merchant.iban_placeholder',
                    'pattern' => 'CV\\d{2}\\d{21}',
                ],
                'help' => 'merchant.iban_help',
                'help_attr' => ['class' => 'form-text text-muted small-text'],
            ])

            ->add('swift', TextType::class, [
                'label' => 'merchant.swift',
                'attr' => [
                    'maxlength' => 11,
                    'placeholder' => 'merchant.swift_placeholder',
                    'pattern' => '[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?',
                ],
                'help' => 'merchant.swift_help',
                'help_attr' => ['class' => 'form-text text-muted small-text'],
            ])

            ->add('nifManeger', TextType::class, [
                'label' => 'merchant.nif',
                'required' => true,
                'attr' => [
                    'placeholder' => 'merchant.nif_placeholder',
                    'maxlength' => 9,
                    'class' => 'form-control border',
                ],
                'help' => 'merchant.nif_help',
                'help_attr' => ['class' => 'form-text text-muted small-text'],
                'constraints' => [
                    new Length([
                        'min' => 9,
                        'max' => 9,
                        'exactMessage' => 'merchant.nif_length',
                    ]),
                    new Regex([
                        'pattern' => '/^[1235]\d{8}$/',
                        'message' => 'merchant.nif_regex',
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
