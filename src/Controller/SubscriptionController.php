<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SubscriptionController extends AbstractController
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient($_ENV['STRIPE_SECRETKEY_ALBUM']);
    }

    #[Route('/subscription', name: 'app_subscription')]
    public function subscribe(
        Security $security,
        SubscriptionRepository $subscriptionRepository
    ): Response {

        $user = $security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // Vérifie si l'utilisateur possède déjà un abonnement actif
        $existingSubscription = $subscriptionRepository->findOneBy([
            'user' => $user,
        ]);

        if (
            $existingSubscription &&
            $existingSubscription->getStatus() === 'active' &&
            $existingSubscription->getEndAt() > new \DateTimeImmutable()
        ) {
            $this->addFlash(
                'info',
                'Vous possédez déjà un abonnement actif.'
            );

            return $this->redirectToRoute('app_albums_index');
        }

        $baseUrl = $_ENV['APP_BASE_URL'];

        try {

            $session = $this->stripe->checkout->sessions->create([
                'mode' => 'subscription',

                'line_items' => [
                    [
                        'price' => $_ENV['STRIPE_MONTHLY_PRICE_ID'],
                        'quantity' => 1,
                    ]
                ],

                'metadata' => [
                    'user_id' => $user->getId(),
                ],

                'success_url' => $baseUrl . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',

                'cancel_url' => $baseUrl . '/album',
            ]);

            return $this->redirect($session->url);

        } catch (ApiErrorException $e) {
            // dd($e->getMessage());

            $this->addFlash(
                'danger',
                'Impossible de créer la session de paiement.'
            );

            return $this->redirectToRoute('app_albums_index');
        }
    }

    #[Route('/subscription/success', name: 'app_subscription_success')]
    public function success(
        Request $request,
        Security $security,
        EntityManagerInterface $em,
        SubscriptionRepository $subscriptionRepository
    ): Response {

        $user = $security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $sessionId = $request->query->get('session_id');

        if (!$sessionId) {
            return $this->redirectToRoute('app_albums_index');
        }

        try {

            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {

                $this->addFlash(
                    'danger',
                    'Le paiement n\'a pas été validé.'
                );

                return $this->redirectToRoute('app_albums_index');
            }

            if (!$session->subscription) {

                $this->addFlash(
                    'danger',
                    'Abonnement Stripe introuvable.'
                );

                return $this->redirectToRoute('app_albums_index');
            }

            $stripeSubscription = $this->stripe->subscriptions->retrieve(
                $session->subscription
            );

        } catch (ApiErrorException $e) {

            $this->addFlash(
                'danger',
                'Impossible de récupérer les informations Stripe.'
            );

            return $this->redirectToRoute('app_albums_index');
        }

        $subscription = $subscriptionRepository->findOneBy([
            'user' => $user,
        ]);

        if (!$subscription) {

            $subscription = new Subscription();

            $subscription->setUser($user);

            $subscription->setCreatedAt(
                new \DateTimeImmutable()
            );
        }

        $subscription->setUpdatedAt(
            new \DateTimeImmutable()
        );

        $subscription->setStatus(
            $stripeSubscription->status
        );

        $subscription->setStripeSubscriptionId(
            $stripeSubscription->id
        );

        $subscription->setStripeCustomerId(
            $stripeSubscription->customer
        );

        $subscription->setStartAt(
            (new \DateTimeImmutable())->setTimestamp(
                $stripeSubscription->current_period_start
            )
        );

        $subscription->setEndAt(
            (new \DateTimeImmutable())->setTimestamp(
                $stripeSubscription->current_period_end
            )
        );

        $em->persist($subscription);
        $em->flush();

        $this->addFlash(
            'success',
            'Votre abonnement mensuel est maintenant actif.'
        );

        return $this->redirectToRoute('app_albums_index');
    }
}