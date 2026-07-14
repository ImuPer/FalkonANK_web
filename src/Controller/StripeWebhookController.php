<?php

namespace App\Controller;

use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Stripe\Webhook;
use App\Entity\Subscription;
use App\Repository\UserRepository;

class StripeWebhookController extends AbstractController
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient($_ENV['STRIPE_SECRETKEY_ALBUM']);
    }


    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        SubscriptionRepository $subscriptionRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {

        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $_ENV['STRIPE_WEBHOOK_SECRET']
            );
        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        }

        if (!$event) {
            return new Response('Invalid payload', 400);
        }


        $type = $event['type'];


        switch ($type) {


            /**
             * Création abonnement
             */
            case 'checkout.session.completed':

                $session = $event['data']['object'];

                if (($session['payment_status'] ?? null) !== 'paid') {
                    break;
                }

                if (
                    empty($session['subscription']) ||
                    empty($session['metadata']['user_id'])
                ) {
                    break;
                }

                $stripeSubscription = $this->stripe
                    ->subscriptions
                    ->retrieve($session['subscription']);

                $user = $userRepository->find(
                    $session['metadata']['user_id']
                );

                if (!$user) {
                    break;
                }

                $subscription = $subscriptionRepository->findOneBy([
                    'user' => $user
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

                break;


            /**
             * Renouvellement / changement abonnement
             */
            case 'customer.subscription.updated':

                $stripeSubscription = $event['data']['object'];


                $subscription =
                    $subscriptionRepository->findOneBy([
                        'stripeSubscriptionId' => $stripeSubscription['id']
                    ]);


                if ($subscription) {

                    $subscription->setStatus(
                        $stripeSubscription['status']
                    );


                    $subscription->setStartAt(
                        (new \DateTimeImmutable())
                            ->setTimestamp(
                                $stripeSubscription['current_period_start']
                            )
                    );

                    $subscription->setEndAt(
                        (new \DateTimeImmutable())
                            ->setTimestamp(
                                $stripeSubscription['current_period_end']
                            )
                    );


                    $subscription->setUpdatedAt(
                        new \DateTimeImmutable()
                    );


                    $em->flush();
                }


                break;



            /**
             * Abonnement supprimé
             */
            case 'customer.subscription.deleted':

                $stripeSubscription = $event['data']['object'];


                $subscription =
                    $subscriptionRepository->findOneBy([
                        'stripeSubscriptionId' => $stripeSubscription['id']
                    ]);


                if ($subscription) {

                    $subscription->setStatus(
                        'canceled'
                    );

                    $subscription->setEndAt(new \DateTimeImmutable());

                    $subscription->setUpdatedAt(
                        new \DateTimeImmutable()
                    );


                    $em->flush();
                }


                break;



            /**
             * Paiement mensuel échoué
             */
            case 'invoice.payment_failed':

                $invoice = $event['data']['object'];


                if (!empty($invoice['subscription'])) {

                    $subscription =
                        $subscriptionRepository->findOneBy([
                            'stripeSubscriptionId' =>
                                $invoice['subscription']
                        ]);


                    if ($subscription) {

                        $subscription->setStatus(
                            'past_due'
                        );


                        $subscription->setUpdatedAt(
                            new \DateTimeImmutable()
                        );


                        $em->flush();
                    }
                }


                break;
        }


        return new Response('Webhook received', 200);
    }
}