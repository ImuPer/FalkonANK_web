<?php

namespace App\Controller\Admin;

use App\Entity\BasketProduct;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Doctrine\Persistence\ManagerRegistry;


class BasketProductCrudController extends AbstractCrudController
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
        return BasketProduct::class;
    }
    // public function configureActions(Actions $actions): Actions
    // {
    //     return $actions
    //         ->add(Crud::PAGE_INDEX, Action::DETAIL)
    //         ->add(Crud::PAGE_EDIT, Action::DELETE);
    // }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // Modifier le titre qui s'affiche sur la page de l'entité BasketProduct
            ->setPageTitle('index', 'Artigos da Encomenda')
            // ->setPageTitle('new', 'Ajouter un Produit au Panier')
            ->setPageTitle('edit', 'Modificar o artigo(s) da Encomenda')
            // ->setPageTitle('detail', 'Détails du Produit du Panier')
        ;
    }
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Retirer l'action "new" sur la page index pour empêcher l'ajout de nouveaux BasketProduct
            ->remove(Crud::PAGE_INDEX, 'new')
            ->remove(Crud::PAGE_INDEX, 'edit')
            ->remove(Crud::PAGE_INDEX, 'delete');

    }

    public function new(AdminContext $context)
    {
        $user = $this->security->getUser();

        // Si l'utilisateur n'est pas un admin, on le redirige vers une autre page
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('easyadmin');
        }

        return parent::new($context);
    }



    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();
        if ($user->getRoles() === [0 => "ROLE_ADMIN", 1 => "ROLE_USER"]) {
            // if ($pageName === Crud::PAGE_INDEX) {
            return [
                AssociationField::new('orderC')
                    ->setLabel('N° Encomenda')
                    ->hideOnForm()
                    ->formatValue(function ($value) {
                        return $value ? $value->getRef() : ''; // 
                    }),

                AssociationField::new('basket.user')
                    ->setLabel('Cliente')
                    ->hideOnForm()
                    ->formatValue(function ($value) {
                        return $value ? $value->getEmail() : '';
                    })
                    ->setCrudController(UserCrudController::class),


                AssociationField::new('product')
                    ->setLabel('Artigo')
                    ->formatValue(function ($value) {
                        return $value ? $value->getName() : ''; // 
                    }),
                IntegerField::new('quantity', 'Quantité')->hideOnForm(),
                MoneyField::new('product.price', 'Preço unitario')
                    ->setCurrency('CVE')->hideOnForm(),

                MoneyField::new('total', 'TOTAL')
                    ->setCustomOption('getter', fn($entity) => $entity->getTotal())
                    ->setCurrency('CVE')->hideOnForm(),

                AssociationField::new('product.shop')
                    ->setLabel('Loja')
                    ->hideOnForm()
                    ->formatValue(function ($value) {
                        return $value ? $value->getName() : '';
                    })
                    ->setCrudController(ShopCrudController::class),

                TextField::new('payment_method', 'Meio de pagamento')->hideOnForm(),
                TextField::new('payment_status', 'Statut pagamento')->hideOnForm(),
                DateTimeField::new('date_pay', 'Data de pagamento')->hideOnForm(),
            ];
        } else {
            return [
                AssociationField::new('orderC')
                    ->setLabel('N° Encomenda')
                    ->hideOnForm()
                    ->formatValue(function ($value) {
                        return $value ? $value->getRef() : ''; // 
                    }),

                AssociationField::new('basket.user')
                    ->setLabel('Cliente')
                    ->hideOnForm()
                    ->formatValue(function ($value) {
                        return $value ? $value->getEmail() : '';
                    })
                    ->setCrudController(UserCrudController::class),

                TextField::new('product.name', 'Artigo')->hideOnForm(),
                MoneyField::new('product.price', 'Preço')
                    ->setRequired(true)->setCurrency('CVE')->hideOnForm(),
                IntegerField::new('quantity', 'Quantité')->hideOnForm(),
                MoneyField::new('total', 'TOTAL')
                    ->setCurrency('CVE')->hideOnForm(),
                // TextField::new('product.shop.name', 'Loja')->hideOnForm(),
                TextField::new('payment_method', 'Pagamento')->hideOnForm(),
                // TextField::new('payment_status', 'Status pagamento')->hideOnForm(),
                DateTimeField::new('date_pay', 'Data pagamento')->hideOnForm(),
                ChoiceField::new('OrderC.orderStatus', 'Esdado encomenda')
                    ->setChoices([
                            'Entregue' => 'Entregue',
                            'Entregue e finalizado' => 'Entregue e finalizado',
                            'Em processamento' => 'Em processamento'
                        ])
                    ->setRequired(true),
                TextareaField::new('OrderC.internalNote', 'Notificação da loja')->hideOnIndex(),
                // Transfert vers BasketProduct
                BooleanField::new('OrderC.refund', 'Reembolso')->hideOnIndex(),
                // TextField::new('refund_status', 'Estado do reembolso'),
                ChoiceField::new('OrderC.refundStatus', 'Estado do reembolso')->hideOnIndex()
                    ->setChoices([
                            'Em curso' => 'Em curso',
                            'Reembolsado' => 'Reembolsado',
                        ]),
                TextareaField::new('OrderC.refundNote', 'Notificação - reembolso')->hideOnIndex(),

            ];
        }
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $user = $this->security->getUser();
        $qb = $this->doctrine->getRepository(BasketProduct::class)->createQueryBuilder('basketProduct')
            ->andWhere('basketProduct.payment = :payment')
            ->andWhere('basketProduct.payment_status = :status')
            ->setParameter('payment', true)
            ->setParameter('status', 'paid');

        // Vérification du rôle de l'utilisateur
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            // Si l'utilisateur est ADMIN, on ne filtre pas par shop/user
            $qb->join('basketProduct.product', 'product')
                ->join('product.shop', 'shop');
        } else {
            // Sinon, on limite les résultats aux produits associés à l'utilisateur connecté
            $qb->join('basketProduct.product', 'product')
                ->join('product.shop', 'shop')
                ->andWhere('shop.user = :user')
                ->setParameter('user', $user);
        }

        return $qb;
    }


    // public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    // {
    //     $user = $this->security->getUser();

    //     $qb = $this->doctrine->getRepository(BasketProduct::class)->createQueryBuilder('basketProduct')
    //         ->andWhere('basketProduct.payment = :payment')
    //         ->andWhere('basketProduct.payment_status = :status')
    //         ->setParameter('payment', true)
    //         ->setParameter('status', 'paid');

    //     $qb->join('basketProduct.product', 'product')
    //         ->join('product.shop', 'shop')
    //         ->andWhere('shop.user = :user')
    //         ->setParameter('user', $user);

    //     return $qb;
    // }

}
