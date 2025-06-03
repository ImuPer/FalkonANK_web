<?php

namespace App\Controller\Admin;

use App\Entity\Basket;
use App\Entity\BasketProduct;
use App\Entity\Order;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;

class OrderCrudController extends AbstractCrudController
{
    private Security $security;

    private ManagerRegistry $doctrine;

    public function __construct(Security $security, ManagerRegistry $doctrine)
    {
        $this->security = $security;
        $this->doctrine = $doctrine;
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // Modifier le titre qui s'affiche sur la page de l'entité BasketProduct
            ->setPageTitle('index', 'Encomendas')
            ->setPageTitle('edit', 'Modificar o Estado da Encomenda');
    }

    public function configureAssets(Assets $assets): Assets
    {
        return Assets::new()
            ->addJsFile('build/refund_toggle.js');
    }


    // Méthode qui surchargera la requête pour filtrer les commandes selon l'utilisateur
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, \EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection $filters): QueryBuilder
    {
        $user = $this->security->getUser();

        // Si l'utilisateur est un "MERCHANT", on filtre en fonction du Shop
        if (in_array("ROLE_MERCHANT", $user->getRoles())) {
            $qb = $this->doctrine->getRepository(Order::class)->createQueryBuilder('o');

            // Joindre la table BasketProduct pour obtenir les produits
            $qb->join('o.basketProducts', 'bp')
                ->join('bp.product', 'p')
                ->join('p.shop', 's')
                ->where('s.user = :merchant')
                ->setParameter('merchant', $user);

            return $qb;
        }

        // Si ce n'est pas un "MERCHANT", renvoie simplement la requête de base
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
    }

    // Facultatif : Si tu veux ajouter des champs dans l'index
    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();
        // Vérifier les rôles de l'utilisateur
        // if (in_array("ROLE_ADMIN", $user->getRoles())) {
        //     // Ajouter les champs spécifiques pour les utilisateurs avec les rôles ADMIN ou USER
        //     return [
        //         MoneyField::new('total_amount', 'Total')->setRequired(true)->setCurrency('CVE')->hideOnForm(),
        //MoneyField::new('amountFinal', 'Total Final')->setFormTypeOption('attr', ['readonly' => true])->setCurrency('CVE')->setHelp('👉 CVE (escudos de Cabo Verde)'),
        //         TextField::new('basketProductsList', 'Todos artigos'),
        
        //         TextField::new('order_status', 'Estatus'),
        //         TextField::new('beneficiary_name', 'Beneficiario')->hideOnForm(),
        //         TextField::new('beneficiary_email', 'Email do Beneficiario')->hideOnForm(),
        //         TextField::new('beneficiary_address', 'adereço do Beneficiario')->hideOnForm(),
        //         TextareaField::new('internal_note', 'Notificação da loja')->hideOnForm(),
        //         TextField::new('customer_note', 'Comentário do cliente')->hideOnForm(),
        //         // Transfert vers BasketProduct
        //         BooleanField::new('refund', 'Reembolso')->hideOnForm(),
        //         TextField::new('refund_status', 'Status do reembolso')->hideOnForm(),
        //         TextareaField::new('refund_note', 'Notificaçõ do reembolso')->hideOnForm(),
        //     ];
        // } else {
        return [
            TextField::new('ref', 'Referencia')
                ->setFormTypeOption('attr', ['readonly' => true]),
            DateTimeField::new('orderDate', 'Data')
                ->hideOnForm()
                ->formatValue(function ($value) {
                    // Vérifier si la valeur est valide avant de la formater
                    return $value ? $value->format('d/m/Y') : ''; // Format français : jour/mois/année
                }),
            MoneyField::new('totalAmount', 'Total')->setFormTypeOption('attr', ['readonly' => true])->setCurrency('CVE')->setHelp('👉 CVE (escudos de Cabo Verde)'),
            TextField::new('beneficiary_name', 'Beneficiario')->hideOnForm(),
            TextField::new('beneficiary_email', 'Email do Beneficiario')->hideOnForm(),
            TextField::new('beneficiary_address', 'adereço do Beneficiario')->hideOnForm(),
            TextField::new('basketProductsList', 'Artigos')->setFormTypeOption('attr', ['readonly' => true]),


            ChoiceField::new('orderStatus', 'Estado da encomenda')
                ->setChoices([
                    'Em processamento' => 'Em processamento',
                    'Reenbolso' => 'Reembolso',
                    'Entregue e finalizado' => 'Entregue e finalizado',
                    // 'Reembolso' => 'Reembolso'

                ])
                ->setRequired(true),
            TextareaField::new('internal_note', 'Notificação da loja')->setRequired(true),
            TextField::new('customer_note', 'Comentário do cliente')->hideOnForm(),

            BooleanField::new('refund', 'Reembolso')
                ->hideOnIndex(),

            TextField::new('refund_amount', 'Montante')
                ->hideOnIndex(),

            ChoiceField::new('refund_status', 'Estado do reembolso')
                ->setChoices([
                    'Em curso' => 'Em curso',
                    'Reembolsado' => 'Reembolsado',
                ])
                ->hideOnIndex(),

            TextareaField::new('refund_note', 'Notificação - reembolso')
                ->hideOnIndex(),

        ];
        // }
    }


    public function configureActions(Actions $actions): Actions
    {
        // if (!$this->isGranted('ROLE_ADMIN')) {
        // Si pas admin, on désactive aussi EDIT et INDEX
        $actions = $actions
            ->disable(Action::DELETE)
            ->disable(Action::NEW);

        // ->disable(Action::INDEX); // 🚫 "Back to list"
        // }
        //  else {
        //     // Si admin, on redéfinit l'action EDIT avec un dialogue de confirmation
        //     $editAction = Action::new(Action::EDIT)
        //         ->linkToCrudAction('edit') // indique que c’est bien une action CRUD standard
        //         ->setCssClass('btn btn-warning') // facultatif
        //         ->setConfirmation('Tem a certeza de que pretende editar esta Encomenda?');

        //     $actions = $actions->add(Crud::PAGE_INDEX, $editAction);
        // }

        // ✅ Ajoute ce return final, obligatoire
        return $actions;
    }



}

