<?php

namespace App\Form;

use App\Entity\Basket;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ref')
            ->add('order_date', null, [
                'widget' => 'single_text',
            ])
            ->add('total_amount')
            ->add('order_status')
            ->add('customer_note')
            ->add('internal_note')
            ->add('refund')
            ->add('refund_status')
            ->add('refund_note')
            ->add('Basket', EntityType::class, [
                'class' => Basket::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
