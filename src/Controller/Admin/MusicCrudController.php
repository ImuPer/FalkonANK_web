<?php

namespace App\Controller\Admin;

use App\Entity\Music;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MusicCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Music::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Musique')
            ->setEntityLabelInPlural('Musiques')
            ->setPageTitle('index', 'Gestion des musiques');
    }

    public function configureFields(string $pageName): iterable
    {
        return [

            IdField::new('id')
                ->hideOnForm(),

            TextField::new('title', 'Titre'),

            TextField::new('artist', 'Artiste'),

            TextField::new('album', 'Album')
                ->hideOnIndex(),

            IntegerField::new('duration', 'Durée (sec)')
                ->hideOnIndex(),

            TextField::new('genre', 'Genre'),

            DateField::new('releaseDate', 'Date de sortie')
                ->hideOnIndex(),

            TextField::new('coverImage', 'Image')
                ->hideOnIndex(),

            TextField::new('audioFile', 'Fichier audio')
                ->hideOnIndex(),

            IntegerField::new('views', 'Vues'),

            BooleanField::new('isPublished', 'Publié'),

            DateTimeField::new('createdAt', 'Créé le')
                ->hideOnForm(),
        ];
    }
}