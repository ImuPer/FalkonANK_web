<?php

namespace App\Controller;

use App\Entity\AlbumPurchase;
use App\Repository\AlbumRepository;
use App\Repository\AlbumPurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlbumPaymentController extends AbstractController
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient($_ENV['STRIPE_SECRETKEY']);
    }

    #[Route('/album/payment/success', name: 'app_album_payment_success')]
    public function success(
        Request $request,
        AlbumRepository $albumRepository,
        AlbumPurchaseRepository $purchaseRepository,
        EntityManagerInterface $em
    ): Response {

        $sessionId = $request->query->get('session_id');

        if (!$sessionId) {
            throw $this->createNotFoundException();
        }

        $session = $this->stripe->checkout->sessions->retrieve($sessionId);

        // paiement non validé
        if ($session->payment_status !== 'paid') {

            return $this->redirectToRoute('app_albums_index');
        }

        $albumId = $session->metadata->album_id;
        $user = $this->getUser();

        $album = $albumRepository->find($albumId);

        if (!$album) {
            throw $this->createNotFoundException();
        }

        // éviter doublon
        $existing = $purchaseRepository->findOneBy([
            'user' => $user,
            'album' => $album
        ]);

        if (!$existing) {

            $purchase = new AlbumPurchase();

            $purchase->setUser($user);
            $purchase->setAlbum($album);

            $purchase->setPurchaseDate(new \DateTime());

            $purchase->setPurchasePrice($album->getPrice());

            $purchase->setCurrency('EUR');

            $purchase->setPaymentStatus('paid');

            $purchase->setPaymentMethod('Stripe');

            $purchase->setTransactionReference($session->payment_intent);

            $purchase->setQuantity(1);

            $purchase->setCreatedAt(new \DateTimeImmutable());

            $em->persist($purchase);
            $em->flush();
        }

        return $this->redirectToRoute('app_music_by_album', [
            'id' => $album->getId()
        ]);
    }
}