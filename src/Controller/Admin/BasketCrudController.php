<?php

namespace App\Controller\Admin;

use App\Entity\Basket;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BasketCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Basket::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('user.firstName', 'user')->hideOnForm(),
              // Utilisation de getter pour accéder à l'utilisateur via basket
            AssociationField::new('user', 'User')
            ->setFormTypeOption('class', User::class)->hideOnForm(),
        ];
    }
    
}
