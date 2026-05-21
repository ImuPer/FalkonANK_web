<?php

namespace App\Controller;

use App\Entity\Album;
use App\Repository\AlbumPurchaseRepository;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

class AlbumController extends AbstractController
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient($_ENV['STRIPE_SECRETKEY']);
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

            'line_items' => [[
                'quantity' => 1,

                'price_data' => [
                    'currency' => 'eur',

                    'product_data' => [
                        'name' => $album->getName(),
                    ],

                    // Stripe utilise centimes
                    'unit_amount' => (int) ($album->getPrice() * 100),
                ],
            ]],

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