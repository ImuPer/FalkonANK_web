<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\AlbumPurchase;
use App\Entity\Music;
use App\Repository\AlbumPurchaseRepository;
use App\Repository\AlbumRepository;
use App\Repository\MusicRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MusicController extends AbstractController
{
    #[Route('/product/{id}/music', name: 'app_music_by_product')]
    public function byProduct(int $id, ProductRepository $productRepository, MusicRepository $musicRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $musics = $musicRepository->findBy(
            [
                'product' => $product,
                'isPublished' => true
            ],
            [
                'track' => 'ASC'
            ]
        );

        return $this->render('music/index.html.twig', [
            'product' => $product,
            'musics' => $musics,
        ]);
    }


    #[Route('/music/{id}/view', name: 'app_music_view', methods: ['POST'])]
    public function incrementView(
        Music $music,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $music->setViews($music->getViews() + 1);

        $entityManager->persist($music);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'views' => $music->getViews()
        ]);
    }
    // --------------------ALBUMS------------------------------------------------------------------
    #[Route('/album', name: 'app_albums_index', methods: ['GET'])]
    public function index(AlbumRepository $albumRepository): Response
    {
        return $this->render('albums/index.html.twig', [
            'albums' => $albumRepository->findAll(),
        ]);
    }

    #[Route('/album/{id}/musics', name: 'app_music_by_album', methods: ['GET'])]
    public function byAlbum(
        int $id,
        AlbumRepository $albumRepository,
        MusicRepository $musicRepository,
        AlbumPurchaseRepository $purchaseRepository,
        Security $security
    ): Response {

        $album = $albumRepository->find($id);

        if (!$album) {
            throw $this->createNotFoundException('Album not found');
        }

        $user = $security->getUser();

        $hasBought = false;

        if ($user) {

            $hasBought = $purchaseRepository->hasUserBoughtAlbum(
                $user->getId(),
                $album->getId()
            );
        }

        $musics = $musicRepository->findBy([
            'album' => $album,
            'isPublished' => true
        ], [
            'track' => 'ASC'
        ]);

        return $this->render('music/index.html.twig', [
            'album' => $album,
            'musics' => $musics,
            'hasBought' => $hasBought
        ]);
    }

    
    #[Route('/album/{id}/buy', name: 'app_album_buy')]
    #[IsGranted('ROLE_USER')]
    public function buy(
        Album $album,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // éviter double achat
        $existing = $em->getRepository(AlbumPurchase::class)->findOneBy([
            'user' => $user,
            'album' => $album
        ]);

        if ($existing) {
            return $this->redirectToRoute('app_music_by_album', ['id' => $album->getId()]);
        }

        $purchase = new AlbumPurchase();
        $purchase->setUser($user);
        $purchase->setAlbum($album);
        $purchase->setPurchaseDate(new \DateTime());
        $purchase->setPurchasePrice($album->getPrice() ?? 0);
        $purchase->setCurrency('EUR');
        $purchase->setPaymentStatus('paid'); // ⚠️ ici à adapter si Stripe/PayPal
        $purchase->setQuantity(1);
        $purchase->setCreatedAt(new \DateTimeImmutable());

        $em->persist($purchase);
        $em->flush();

        return $this->redirectToRoute('app_music_by_album', [
            'id' => $album->getId()
        ]);
    }

}