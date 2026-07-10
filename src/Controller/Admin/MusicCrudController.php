<?php

namespace App\Controller\Admin;

use App\Entity\Music;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
class MusicCrudController extends AbstractCrudController
{
    private string $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
    }
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

    public function configureActions(Actions $actions): Actions
    {
        $deleteImage = Action::new('deleteImage', '🗑 Supprimer image')
            ->linkToCrudAction('deleteImage');

        return $actions
            ->add(Crud::PAGE_INDEX, $deleteImage)
            ->add(Crud::PAGE_DETAIL, $deleteImage);
    }

    public function configureFields(string $pageName): iterable
    {
        return [

            // IdField::new('id')->hideOnForm(),
            IntegerField::new('track', 'Piste')->setRequired(true),

            TextField::new('title', 'Titre')->setRequired(true),

            TextField::new('artist', 'Artiste'),

            TextField::new('composer', 'Compositeur')->setRequired(true)->hideOnIndex(),

            TextEditorField::new('lyrics', 'Paroles')->hideOnIndex(),

            AssociationField::new('album', 'Album')->setRequired(true)->autocomplete(),

            IntegerField::new('duration', 'Durée (sec)')->hideOnIndex(),

            TextField::new('genre', 'Genre')->hideOnIndex(),

            DateField::new('releaseDate', 'Date de sortie')->hideOnIndex(),

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

            // =========================
            // AUDIO UPLOAD
            // =========================
            TextField::new('audioFileFile', 'Fichier audio')
                ->setFormType(FileType::class)
                ->onlyOnForms()
                // ->setRequired(true)
                ->setHelp(
                    'Upload audio (MP3, WAV, etc. - conversion auto)'
                ),

            TextField::new('audioFile', 'Audio')
                ->onlyOnIndex()
                ->formatValue(function ($value) {
                    if (!$value)
                        return null;

                    return sprintf(
                        '<audio controls style="width:180px;">
                            <source src="/uploads/music/%s" type="audio/mpeg">
                        </audio>',
                        $value
                    );
                })
                ->renderAsHtml(),

            IntegerField::new('views', 'Vues'),

            BooleanField::new('isPublished', 'Publié'),

            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),

            AssociationField::new('product', 'Produit')
                ->autocomplete(),
        ];
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Music) {
            return;
        }

        $this->convertAudioToMp3($entityInstance);

        $entityInstance->setUpdatedAt(new \DateTimeImmutable());

        parent::persistEntity($entityManager, $entityInstance);
    }

    private function convertAudioToMp3(Music $music): void
    {
        if (!$music->getAudioFileFile()) {
            return;
        }

        $file = $music->getAudioFileFile();

        $input = $file->getPathname();

        $outputFile = uniqid() . '.mp3';

        $outputPath = $this->projectDir . '/public/uploads/music/' . $outputFile;

        $ffmpeg = 'ffmpeg';

        $cmd = sprintf(
            '%s -i %s -b:a 192k %s',
            $ffmpeg,
            escapeshellarg($input),
            escapeshellarg($outputPath)
        );

        shell_exec($cmd);

        $music->setAudioFile($outputFile);
    }

    public function deleteEntity(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        $entityInstance
    ): void {

        if (!$entityInstance instanceof Music) {
            return;
        }

        // =========================
        // DELETE COVER IMAGE
        // =========================

        if ($entityInstance->getCoverImage()) {

            $imagePath = $this->projectDir
                . '/public/uploads/images/'
                . $entityInstance->getCoverImage();

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // =========================
        // DELETE AUDIO FILE
        // =========================

        if ($entityInstance->getAudioFile()) {

            $audioPath = $this->projectDir
                . '/public/uploads/music/'
                . $entityInstance->getAudioFile();

            if (file_exists($audioPath)) {
                unlink($audioPath);
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function updateEntity(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        $entityInstance
    ): void {

        if (!$entityInstance instanceof Music) {
            return;
        }

        // =========================
        // OLD IMAGE DELETE
        // =========================

        $originalData = $entityManager
            ->getUnitOfWork()
            ->getOriginalEntityData($entityInstance);

        $oldImage = $originalData['coverImage'] ?? null;

        if (
            $entityInstance->getCoverImageFile()
            && $oldImage
        ) {

            $oldImagePath = $this->projectDir
                . '/public/uploads/images/'
                . $oldImage;

            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        $entityInstance->setUpdatedAt(new \DateTimeImmutable());

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteImage(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        \Symfony\Component\HttpFoundation\Request $request
    ): RedirectResponse {

        $id = $request->query->get('entityId');

        $music = $entityManager
            ->getRepository(Music::class)
            ->find($id);

        if (!$music) {
            return $this->redirect($request->headers->get('referer'));
        }

        if ($music->getCoverImage()) {

            $imagePath = $this->projectDir
                . '/public/uploads/images/'
                . $music->getCoverImage();

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            $music->setCoverImage(null);

            $entityManager->persist($music);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Image supprimée.');

        return $this->redirect($request->headers->get('referer'));
    }

}