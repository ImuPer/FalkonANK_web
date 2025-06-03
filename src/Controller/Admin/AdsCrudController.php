<?php

namespace App\Controller\Admin;

use App\Entity\Ads;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdsCrudController extends AbstractCrudController
{
    public const ACTION_DUPLICATE = "duplicate";
    public const ADS_BASE_PATH = 'upload/images/ads';
    public const ADS_UPLOAD_DIR = 'public/upload/images/ads';

    public static function getEntityFqcn(): string
    {
        return Ads::class;
    }

    
    public function configureFields(string $pageName): iterable
{
    $isCreatePage = $pageName === Crud::PAGE_NEW; // Verifica se é a página de criação
    
    return [
        ChoiceField::new('title', 'Título (tipo)')
            ->setChoices([
                'Anuncio' => 'ads', 
                'Imagem' => 'img',
                'Loja' => 'shop',
                'Artigo' =>'product'
            ])
            ->setRequired(true),
        TextareaField::new('description', 'Descrição'),
        TextField::new('urlAds', 'URL do Anúncio'),  // Adicionando um label para a URL
        ImageField::new('img', 'Imagem')
            ->setBasePath(self::ADS_BASE_PATH)
            ->setUploadDir(self::ADS_UPLOAD_DIR)
            ->setSortable(false)
            ->setUploadedFileNamePattern('[randomhash].[extension]'),

        AssociationField::new('shop'),
    ];
}

    
}
