<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        $user=$this->getUser();
        if($user->getRoles()===[0=>"ROLE_ADMIN", 1 => "ROLE_USER"]){
            return [
                TextField::new('lastName', 'Last Name')->hideOnForm(),
                TextField::new('firstName', 'First Name')->hideOnForm(),
                TextField::new('email'),
                ChoiceField::new('roles')->renderAsBadges()->allowMultipleChoices()->setChoices(['Client'
                    =>'ROLE_USER',
                    'Comerciante'=>'ROLE_MERCHANT',
                    'Carrier'=>'ROLE_CARRIER',
                ])    ,
            ];
        }
        else{
            return [
                TextField::new('lastName', 'Apelido')->hideOnForm(),
                TextField::new('firstName', 'Nome')->hideOnForm(),
                TextField::new('email'),
            ];

        }
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
    // else {
    //     // Si admin, on redéfinit l'action EDIT avec un dialogue de confirmation
    //     $editAction = Action::new(Action::EDIT)
    //         ->linkToCrudAction('edit') // indique que c’est bien une action CRUD standard
    //         ->setCssClass('btn btn-warning') // facultatif, change l’apparence
    //         ->setIcon('fa fa-edit') // facultatif aussi
    //         ->setConfirmation('Tem a certeza de que pretende editar este utilizador ?');

    //     // On remplace l'action existante par notre version custom
    //     $actions = $actions->add(Crud::PAGE_INDEX, $editAction);
    // }

    return $actions;
}
    
}
