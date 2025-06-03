<?php

namespace App\Controller\Admin;

use App\Entity\City;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return City::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
           IdField::new('id')->hideOnForm(),
           TextField::new('name'),
           DateTimeField::new('date_at')->hideOnForm(),
           DateTimeField::new('date_up')->hideOnForm(),
        ];
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
{
    if (!$entityInstance instanceof City) {
        return;
    }

    // Définir la date de création et l'utilisateur qui crée la catégorie
    $entityInstance->setDateAt(new \DateTimeImmutable);

    // Appeler la méthode parente pour enregistrer l'entité
    parent::persistEntity($entityManager, $entityInstance);
}


    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance):void
    {
        if(!$entityInstance instanceof City) return;
        $entityInstance->setDateUp(new \DateTimeImmutable);
        // dd($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureActions(Actions $actions): Actions
{
    $user = $this->getUser();

    // Supprimer l'action DELETE pour tout le monde
    $actions = $actions->disable(Action::DELETE);

    if (!$this->isGranted('ROLE_ADMIN')) {
        // Si pas admin, on désactive aussi EDIT
        $actions = $actions
        ->disable(Action::EDIT)
        ->disable(Action::INDEX); // 🚫 "Back to list"
    } 
    return $actions;
}
    
}
