<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Repository\ShopRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ProductCrudController extends AbstractCrudController
{
    public $shopRepository;
    public const ACTION_DUPLICATE = "duplicate";
    public const PRODUCTS_BASE_PATH = 'upload/images/products';
    public const PRODUCTS_UPLOAD_DIR = 'public/upload/images/products';

    public function __construct(ShopRepository $shopRepository)
    {

        $this->shopRepository = $shopRepository;

    }



    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Produtos')
            ->setEntityLabelInSingular('Produto')
            ->setPageTitle(Crud::PAGE_INDEX, 'Produtos')
            ->setPageTitle(Crud::PAGE_EDIT, 'Editar Produto')
            ->setPageTitle(Crud::PAGE_NEW, 'Novo Produto')
            ->setPageTitle(Crud::PAGE_DETAIL, fn(Product $product) => (string) $product->getName());
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $duplicate = Action::new(self::ACTION_DUPLICATE)
            ->linkToCrudAction('duplicateProduct')
            ->setCssClass('btn btn-info');

        if ($this->isGranted('ROLE_ADMIN')) {
        // Si admin, on désactive aussi INDEX
        $actions = $actions
            ->disable(Action::NEW);
        }


        return $actions
            ->add(Crud::PAGE_EDIT, $duplicate)
            ->reorder(Crud::PAGE_EDIT, [self::ACTION_DUPLICATE, Action::SAVE_AND_RETURN]);

    }

    public function configureFields(string $pageName): iterable
    {
        $isCreatePage = $pageName === Crud::PAGE_NEW; // Vérifie si c'est la page de création
        $isEditPage = $pageName === Crud::PAGE_EDIT;  // Vérifie si c'est la page d'édition

        return [
            TextField::new('name', 'Nome do Produto')->setRequired(true),
            MoneyField::new('price', 'Preço')->setRequired(true)->setCurrency('CVE')
            ->setHelp('👉 CVE (escudos de Cabo Verde)'),
            TextField::new('label', 'Rótulo de Produto')->setRequired(true),
            TextareaField::new('description')->setRequired(true),
            ImageField::new('img', 'imagem')
                ->setBasePath(self::PRODUCTS_BASE_PATH)
                ->setUploadDir(self::PRODUCTS_UPLOAD_DIR)
                ->setSortable(false)
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                //->onlyOnForms()  // Afficher seulement dans le formulaire
                ->setHelp('Em caso de modificação, se não selecionar uma nova imagem, a imagem atual será mantida.')
                ->setRequired($isCreatePage),  // L'image est requise uniquement sur la page de création (Add Product)

            AssociationField::new('category')->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                $queryBuilder->where('entity.active = true');
            }),
            AssociationField::new('shop', 'Loja')
                ->setQueryBuilder(function (QueryBuilder $qb) {
                    $user = $this->getUser();
                    if ($user->isMerchant()) {
                        $qb->where('entity.user = :user')
                            ->setParameter('user', $user);
                    }
                })
                ->setRequired(true),
            DateTimeField::new('date_at', 'Criação')->hideOnForm(),
            IntegerField::new('stock')->setRequired(true)->setHelp('O produto só será exibido aos clientes se o stock for superior a 4.'),
            BooleanField::new('active')->setHelp('(👉 O produto só será exibido aos clientes se estiver ativado.)'),

            DateTimeField::new('update_at', 'Modificação')->hideOnForm(),
        ];
    }



    public function duplicateProduct(
        AdminContext $adminContext,
        AdminUrlGenerator $adminUrlGenerator,
        \Doctrine\ORM\EntityManagerInterface $em
    ): RedirectResponse {
        //  dd($adminContext);
        /** @var Product $product  */
        $product = $adminContext->getEntity()->getInstance();

        $duplicateProduct = clone $product;

        parent::persistEntity($em, $duplicateProduct);

        $url = $adminUrlGenerator->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($duplicateProduct->getId())
            ->generateUrl();

        return $this->redirect($url);

    }


    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $this->getUser();
        if (!$entityInstance instanceof Product)
            return;
        $entityInstance->setDateAt(new \DateTimeImmutable);
        $entityInstance->setUserEmail($user->getEmail());

        //    shop du merchant
        $merchant = $this->getUser();
        $shop = $this->shopRepository->findOneBy(['user' => $merchant]);
        $entityInstance->setShop($shop);

        parent::persistEntity($entityManager, $entityInstance);

    }

    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product)
            return;
        $entityInstance->setUpdateAt(new \DateTimeImmutable);
        parent::updateEntity($entityManager, $entityInstance);
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $user = $this->getUser();
        if ($user->getRoles() === [0 => "ROLE_MERCHANT", 1 => "ROLE_USER"])// if active user is Merchant
        {
            $userEmail = $user->getEmail(); // Merchant-email (current user)
            if ($userEmail !== null) {// all products for mail of user-merchant
                $qb->andWhere('entity.user_email = :email')
                    ->setParameter('email', $userEmail);
            }
        }
        return $qb;
    }


}
