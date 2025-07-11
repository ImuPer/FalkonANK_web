<?php

namespace App\Controller\Admin;

use App\Entity\Merchant;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Text;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


class MerchantCrudController extends AbstractCrudController
{

    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Merchant::class;
    }


    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            return [

                TextEditorField::new('user')
                    ->setLabel('User')
                    ->hideOnForm()
                    ->formatValue(function ($value) {
                        return $value ? $value->getEmail() : ''; // Afficher l'email de l'utilisateur
                    }),
                TextField::new('name', 'ShopName')->hideOnForm(),
                TextField::new('city.name', 'City')->hideOnForm(),
                TextEditorField::new('address', 'Address')->hideOnForm(),
                TextEditorField::new('description', 'Desc')->hideOnForm(),
                DateTimeField::new('createdAt', 'Data')->hideOnForm(),
                BooleanField::new('is_approved', 'Aprovado')
                    ->setFormTypeOption('disabled', true) // desativa edição no formulário (edição rápida)
                    ->onlyOnIndex(),
                TextEditorField::new('nifManeger', 'NIF')->hideOnForm(),

                // bank infos 
                TextEditorField::new('bankName', 'Banco')->hideOnForm(),
                TextEditorField::new('bankHolder', 'Titular')->hideOnForm(),
                TextEditorField::new('iban', 'IBAN')->hideOnForm(),
                TextEditorField::new('swift', 'BIC/Swift')->hideOnForm(),


                TextField::new('licenseFile', 'License File')
                    ->formatValue(function (?string $value, Merchant $entity) {
                        if ($value) {
                            return sprintf('<a href="/%s" target="_blank">View File</a>', $value);
                        }
                        return 'No file uploaded';
                    })->hideOnForm(),


                TextEditorField::new('reponse'),
            ];
        }
        if ($pageName === Crud::PAGE_NEW) {
            return [
                //afficher un message
                // $this->addFlash('success', 'Your action was successful!'),
            ];
        } else {
            return [

                TextareaField::new('reponse'),

                BooleanField::new('is_approved', 'Aprovado')
                    ->hideOnIndex()    // aparece só no formulário (new/edit), editável
                ,


            ];
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $entityInstance->getUser(); // Récupérer l'utilisateur lié

        if (!$entityInstance instanceof Merchant) {
            return;
        }

        // Si le marchand est approuvé, on attribue uniquement le rôle ROLE_MERCHANT
        if ($entityInstance->isApproved()) {

            if ($user) {
                $user->setRoles(['ROLE_MERCHANT']); // Remplace les rôles par ROLE_MERCHANT
                // Met à jour le champ "merchant" à true
                // $user->setMerchant(true);
                $entityManager->persist($user);    // Sauvegarde des modifications

                // Se o campo 'reponse' estiver vazio, preenche com o texto automático
                if (empty(trim($entityInstance->getReponse()))) {
                    $lastName = $user->getLastName();
                    $automaticResponse = sprintf(
                        '%s, seja bem vindo (a). O seu pedido foi aceite! E pode aceder ao seu espaço Comerciante através do "botão comerciante" ou o "ícone azul" no cabeçalho da página.',
                        $lastName
                    );
                    $entityInstance->setReponse($automaticResponse);
                    $this->addFlash('success', 'Comerciante aprovado com sucesso!');
                }
            }
        } else {
            if ($user) {
                // Define o papel padrão ROLE_USER
                $user->setRoles(['ROLE_USER']);
                $entityManager->persist($user);

                // Se o campo 'reponse' estiver vazio, preenche com texto automático
                if (empty(trim($entityInstance->getReponse()))) {
                    $lastName = $user->getLastName();
                    $automaticResponse = sprintf(
                        '%s, infelizmente o seu pedido não foi aceite devido a falta ou erro nas informações fornecidas. Para mais informações, contacte-nos através do botão "Contacto" no início da página.',
                        $lastName
                    );
                    $entityInstance->setReponse($automaticResponse);
                    $this->addFlash('danger', 'Comerciante des-aprovado!');
                }
            }
        }


        parent::updateEntity($entityManager, $entityInstance);
    }

}
