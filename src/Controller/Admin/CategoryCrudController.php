<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }
    public function configureActions(Actions $actions): Actions
    {
        // Récupérer l'utilisateur actuel
        $user = $this->getUser();

        // Vérifier si l'utilisateur a le rôle 'ROLES_ADMIN'
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            // Désactiver les actions 'edit' et 'delete' pour les utilisateurs sans le rôle 'ROLE_ADMIN'
            $actions->disable(Action::EDIT);
            $actions->disable(Action::DELETE);
            $actions->disable(Action::NEW);
        }

        return $actions;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            // IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextareaField::new('description'),
            BooleanField::new('active'),
            DateTimeField::new('date_at')->hideOnForm(),
            DateTimeField::new('update_at')->hideOnForm(),
        ];
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
{
    if (!$entityInstance instanceof Category) {
        return;
    }

    // Définir la date de création et l'utilisateur qui crée la catégorie
    $entityInstance->setDateAt(new \DateTimeImmutable);
    $entityInstance->setUser($this->getUser());

    // Appeler la méthode parente pour enregistrer l'entité
    parent::persistEntity($entityManager, $entityInstance);
}


    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance):void
    {
        if(!$entityInstance instanceof Category) return;
        $entityInstance->setUpdateAt(new \DateTimeImmutable);
        // dd($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    
}
