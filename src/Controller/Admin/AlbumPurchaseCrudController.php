<?php

namespace App\Controller\Admin;

use App\Entity\AlbumPurchase;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class AlbumPurchaseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AlbumPurchase::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Compra de Álbum')
            ->setEntityLabelInPlural('Compras de Álbuns')
            ->setDefaultSort(['purchaseDate' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW , Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            AssociationField::new('user', 'Utilizador'),
            AssociationField::new('album', 'Álbum'),

            DateTimeField::new('purchaseDate', 'Data da Compra'),

            MoneyField::new('purchasePrice', 'Preço')
                ->setCurrencyPropertyPath('currency')
                ->setStoredAsCents(false),

            TextField::new('currency', 'Moeda'),
            IntegerField::new('quantity', 'Quantidade'),

            TextField::new('paymentMethod', 'Método de Pagamento'),
            TextField::new('paymentStatus', 'Estado do Pagamento'),
            TextField::new('transactionReference', 'Referência da Transação'),

            TextField::new('invoiceNumber', 'Nº da Fatura'),
            UrlField::new('invoiceUrl', 'URL da Fatura')
                ->hideOnIndex(),

            TextField::new('customerName', 'Nome do Cliente'),
            TextField::new('customerEmail', 'Email do Cliente'),
            TextField::new('customerPhone', 'Telefone do Cliente'),

            DateTimeField::new('createdAt', 'Criado em'),
            DateTimeField::new('updatedAt', 'Atualizado em')
                ->hideOnIndex(),
        ];
    }
}