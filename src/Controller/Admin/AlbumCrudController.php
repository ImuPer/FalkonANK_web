<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class AlbumCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Album')
            ->setEntityLabelInPlural('Albums')
            ->setPageTitle('index', 'Gestion des albums');
    }

    public function configureFields(string $pageName): iterable
    {
        return [

            IdField::new('id')
                ->hideOnForm(),

            TextField::new('name', 'Nom de l’album'),

            DateField::new('releaseDate', 'Date de sortie'),

            TextField::new('recordLabel', 'Maison de disque'),

            DateTimeField::new('createdAt', 'Créé le')
                ->hideOnForm(),
                // =========================
            // IMAGE UPLOAD + PREVIEW
            // =========================
            ImageField::new('coverImage', 'Image')
                ->setBasePath('/uploads/images')
                ->onlyOnIndex(),

            TextField::new('coverImageFile', 'Image (upload)')
                ->setFormType(FileType::class)
                ->onlyOnForms()
                ->setHelp('Upload une image (jpg, png, etc.)'),
        ];
    }

    public function persistEntity(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        $entityInstance
    ): void {

    
        if (!$entityInstance instanceof Album) {
            return;
        }

        $entityInstance->setCreatedAt(new \DateTimeImmutable());

        parent::persistEntity($entityManager, $entityInstance);
    }
}