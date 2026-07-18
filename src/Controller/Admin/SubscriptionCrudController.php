<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Subscrição')
            ->setEntityLabelInPlural('Subscrições')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            AssociationField::new('user', 'Utilizador'),

            TextField::new('status', 'Estado'),

            DateTimeField::new('startAt', 'Início'),
            DateTimeField::new('endAt', 'Fim'),

            TextField::new('stripeSubscriptionId', 'Stripe Subscription ID')
                ->hideOnIndex(),

            TextField::new('stripeCustomerId', 'Stripe Customer ID')
                ->hideOnIndex(),

            DateTimeField::new('createdAt', 'Criado em'),
            DateTimeField::new('updatedAt', 'Atualizado em')
                ->hideOnIndex(),

            CollectionField::new('subscriptionInvoices', 'Faturas')
                ->onlyOnDetail(),
        ];
    }
}