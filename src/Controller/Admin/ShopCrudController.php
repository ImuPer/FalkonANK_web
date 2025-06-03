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
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ShopCrudController extends AbstractCrudController
{

    public const SHOP_BASE_PATH = 'upload/images/shops';
    public const SHOP_UPLOAD_DIR = 'public/upload/images/shops';
    public static function getEntityFqcn(): string
    {
        return Shop::class;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $user = $this->getUser();
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
    
        if ($user && ($user->getRoles() === [0=>"ROLE_MERCHANT", 1 => "ROLE_USER"])) {
            $queryBuilder
                ->andWhere(sprintf('%s.user = :user', $queryBuilder->getRootAliases()[0]))
                ->setParameter('user', $user);
        }
    
        return $queryBuilder;
    }
   
    public function configureFields(string $pageName): iterable
    {
         
        return [
            TextField::new('name', 'Nome da Loja'),
            TextField::new('adress', 'Endereço'),
            TextField::new('phone', 'Téléfone fixo'),
            TextField::new('mobile_phone', 'Movel'),
            TextField::new('email', 'Email da loja'),
            TextField::new('description', 'Descriçao'),
            TextField::new('horario', 'Horario '),
            TextField::new('city.name', 'Cidade')->hideOnForm(),
            TextField::new('user.email', 'Email du Gerente'),
            ImageField::new('img', 'imagem')
                ->setBasePath(self::SHOP_BASE_PATH)
                ->setUploadDir(self::SHOP_UPLOAD_DIR)
                ->setSortable(false)
                ->setUploadedFileNamePattern('[randomhash].[extension]'),
        ];

    }

    public function configureActions(Actions $actions): Actions
    {
        $user = $this->getUser();    
        $actions = $actions->disable(Action::DELETE);

        if (!$this->isGranted('ROLE_ADMIN')) {
            // Si admin, on désactive aussi EDIT
            $actions = $actions
            ->disable(Action::DELETE);
        } 
        return $actions;
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance):void
   {
    $user = $this->getUser();
    $entityInstance->setMerchant($user);
    
    $user->setMerchant(true);

    parent::persistEntity($entityManager, $entityInstance);

    $entityManager->persist($user);
    $entityManager->flush();
   
   }   


}
