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
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MusicController extends AbstractController
{
    private StripeClient $stripe;
    public function __construct()
    {
        $this->stripe = new StripeClient($_ENV['STRIPE_SECRETKEY']);
    }
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
    public function index(AlbumRepository $albumRepository, Security $security): Response
    {
        if ($security->isGranted('ROLE_ADMIN')) {
            $albums = $albumRepository->findAll();
        } else {
            $albums = $albumRepository->findBy([
                'isPublished' => true
            ]);
        }

        return $this->render('albums/index.html.twig', [
            'albums' => $albums,
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

        $purchase = $purchaseRepository->findOneBy([
            'user' => $user,
            'album' => $album
        ]);
        foreach ($user->getAlbumPurchases() as $purchase) {
    dump(
        $purchase->getId(),
        $purchase->getAlbum()->getId(),
        $purchase->getAlbum()->getName()
    );
}
die();

        return $this->render('music/index.html.twig', [
            'album' => $album,
            'musics' => $musics,
            'purchase' => $purchase,
            'hasBought' => $hasBought
        ]);
    }

    #[Route('/album/{id}/buy', name: 'app_album_buy')]
    #[IsGranted('ROLE_USER')]
    public function buy(
        Album $album,
        AlbumPurchaseRepository $albumPurchaseRepository
    ): Response {

        $user = $this->getUser();

        // vérifier si déjà acheté
        $existing = $albumPurchaseRepository->findOneBy([
            'user' => $user,
            'album' => $album
        ]);

        if ($existing) {
            return $this->redirectToRoute('app_music_by_album', [
                'id' => $album->getId()
            ]);
        }

        $baseUrl = $_ENV['APP_BASE_URL'];

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',

            'line_items' => [
                [
                    'quantity' => 1,

                    'price_data' => [
                        'currency' => 'eur',

                        'product_data' => [
                            'name' => $album->getName(),
                        ],

                        // Stripe utilise centimes
                        'unit_amount' => (int) ($album->getPrice()),
                    ],
                ]
            ],

            'success_url' => $baseUrl . '/album/payment/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $baseUrl . '/album/' . $album->getId() . '/musics',

            // IMPORTANT
            'metadata' => [
                'album_id' => $album->getId(),
                'user_id' => $user->getId(),
            ],
        ]);

        return $this->redirect($session->url);
    }

}