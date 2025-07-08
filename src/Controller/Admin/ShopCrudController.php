<?php

namespace App\Controller\Admin;

use App\Entity\Shop;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Doctrine\Persistence\ManagerRegistry;

class ShopCrudController extends AbstractCrudController
{
    private ManagerRegistry $doctrine;
    public const SHOP_BASE_PATH = 'upload/images/shops';
    public const SHOP_UPLOAD_DIR = 'public/upload/images/shops';
    public static function getEntityFqcn(): string
    {
        return Shop::class;
    }

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $user = $this->getUser();
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($user && ($user->getRoles() === [0 => "ROLE_MERCHANT", 1 => "ROLE_USER"])) {
            $queryBuilder
                ->andWhere(sprintf('%s.user = :user', $queryBuilder->getRootAliases()[0]))
                ->setParameter('user', $user);
        }

        return $queryBuilder;
    }

    public function configureFields(string $pageName): iterable
    {

        return [
            TextField::new('name', 'Nome da Loja')->setRequired(true),
            TextField::new('adress', 'Endereço')->setRequired(true),
            TextField::new('phone', 'Téléfone fixo'),
            TextField::new('mobile_phone', 'Movel')->setRequired(true),

            TextEditorField::new('email', 'Email da loja')->hideOnForm(),
            TextField::new('email', 'Email da loja')->setRequired(true)->hideOnIndex()->setHelp('👉 é o nome que vai aparcer para os clientes!'),

            TextEditorField::new('description', 'Descriçao')->hideOnForm(),
            TextareaField::new('description', 'Descriçao')->setRequired(true)->hideOnIndex(),

            TextEditorField::new('horario', 'Horario ')->hideOnForm(),
            TextareaField::new('horario', 'Horario ')->setRequired(true)->hideOnIndex(),

            AssociationField::new('city', 'Cidade')->setRequired(true),

            TextEditorField::new('user.email', 'Email du Gerente')->hideOnForm(),

            ImageField::new('img', 'imagem')
                ->setBasePath(self::SHOP_BASE_PATH)
                ->setUploadDir(self::SHOP_UPLOAD_DIR)
                ->setSortable(false)
                ->setUploadedFileNamePattern('[randomhash].[extension]'),
            BooleanField::new('active')->setHelp('(👉 A Loja e os seus produtos só será exibido aos clientes se estiver ativado.)'),

        ];

    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $actions->disable(Action::DELETE);

        if ($this->isGranted('ROLE_ADMIN')) {
            $actions = $actions->disable(Action::NEW)
            // ->disable(Action::EDIT)
            ;
        }

        // Si l'utilisateur a déjà un shop, désactiver le bouton "ajouter"
        if ($this->userHasShop()) {
            $actions = $actions->disable(Action::NEW);
        }

        return $actions;
    }

    //    méthode pour vérifier si l'utilisateur a déjà un shop
    private function userHasShop(): bool
    {
        $user = $this->getUser();
        $shopRepository = $this->doctrine->getRepository(Shop::class);

        return $shopRepository->findOneBy(['user' => $user]) !== null;
    }



    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $this->getUser();
        $entityInstance->setMerchant($user);

        $user->setMerchant(true);

        parent::persistEntity($entityManager, $entityInstance);

        $entityManager->persist($user);
        $entityManager->flush();

    }


}
