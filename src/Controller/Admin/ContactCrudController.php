<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class ContactCrudController extends AbstractCrudController
{
     private $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->hideOnForm(),
            TextField::new('email')->hideOnForm(),
            TextEditorField::new('subject', 'Assunto')->hideOnForm(),
            TextEditorField::new('message')->hideOnForm(),
            DateTimeField::new('datAct','Data')->hideOnForm(),
            TextEditorField::new('response.response', 'Réponse'),
        ];
    }

    
    public function configureActions(Actions $actions): Actions
    {
        $reponse = Action::new('reponse', 'Réponse')
            ->linkToUrl(function (Contact $contact) {
                // Génère l'URL vers ta route 'reponse' en passant l'id
                return $this->generateUrl('reponse', ['id' => $contact->getId()]);
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $reponse)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['datAct' => 'DESC']); // Tri décroissant sur la date
    }
}
