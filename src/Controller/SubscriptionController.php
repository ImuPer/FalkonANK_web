<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\SubscriptionInvoiceRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\SubscriptionInvoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

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
        SubscriptionRepository $subscriptionRepository,
        SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        MailerInterface $mailer
    ): Response {

        $user = $security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }


        $sessionId = $request->query->get('session_id');

        if (!$sessionId) {
            return $this->redirectToRoute('app_albums_index');
        }


        // Protection double validation Stripe
        $existingInvoice = $subscriptionInvoiceRepository->findOneBy([
            'stripeSessionId' => $sessionId
        ]);


        if ($existingInvoice) {

            $this->addFlash(
                'info',
                'Votre abonnement a déjà été enregistré.'
            );

            return $this->redirectToRoute('app_albums_index');
        }



        try {

            $session = $this->stripe->checkout->sessions->retrieve(
                $sessionId
            );


            if (
                $session->status !== 'complete'
                ||
                $session->payment_status !== 'paid'
            ) {

                $this->addFlash(
                    'danger',
                    'Paiement Stripe non validé.'
                );

                return $this->redirectToRoute('app_albums_index');
            }



            if (!$session->subscription) {

                throw new \Exception(
                    'Subscription Stripe manquante'
                );
            }



            $stripeSubscription =
                $this->stripe->subscriptions->retrieve(
                    $session->subscription
                );



            if (!isset($stripeSubscription->items->data[0])) {

                throw new \Exception(
                    'Prix Stripe introuvable'
                );
            }



            $price =
                $stripeSubscription
                    ->items
                    ->data[0]
                    ->price;



            $amountPaid =
                $price->unit_amount / 100;



            $currency =
                strtoupper($price->currency);



            $customer =
                $this->stripe->customers->retrieve(
                    $stripeSubscription->customer
                );



            $customerName =
                $customer->name ?? '';



            $customerEmail =
                $customer->email
                ??
                $user->getEmail();



        } catch (\Throwable $e) {


            $this->addFlash(
                'danger',
                'Erreur récupération Stripe : ' . $e->getMessage()
            );


            return $this->redirectToRoute(
                'app_albums_index'
            );

        }




        /*
        |--------------------------------------------------------------------------
        | Subscription database
        |--------------------------------------------------------------------------
        */


        $subscription =
            $subscriptionRepository->findOneBy([
                'user' => $user
            ]);



        if (!$subscription) {

            $subscription = new Subscription();

            $subscription->setUser($user);

            $subscription->setCreatedAt(
                new \DateTimeImmutable()
            );
        }



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
            (new \DateTimeImmutable())
                ->setTimestamp(
                    $stripeSubscription->current_period_start
                )
        );


        $subscription->setEndAt(
            (new \DateTimeImmutable())
                ->setTimestamp(
                    $stripeSubscription->current_period_end
                )
        );


        $subscription->setUpdatedAt(
            new \DateTimeImmutable()
        );


        $em->persist($subscription);

        $em->flush();




        /*
        |--------------------------------------------------------------------------
        | Invoice interne
        |--------------------------------------------------------------------------
        */


        $invoice = new SubscriptionInvoice();


        $invoice->setUser($user);


        $invoice->setSubscription(
            $subscription
        );


        $invoice->setStripeSessionId(
            $sessionId
        );


        $invoice->setInvoiceNumber(
            sprintf(
                'SUB-%s-%06d',
                date('Y'),
                random_int(100000, 999999)
            )
        );


        $invoice->setAmount(
            $amountPaid
        );


        $invoice->setCurrency(
            $currency
        );


        $invoice->setPaymentStatus(
            'paid'
        );


        $invoice->setCreatedAt(
            new \DateTimeImmutable()
        );


        $em->persist($invoice);

        $em->flush();





        /*
        |--------------------------------------------------------------------------
        | Génération PDF
        |--------------------------------------------------------------------------
        */


        $html =
            $this->renderView(
                'invoice/subscription_invoice.html.twig',
                [
                    'invoice' => $invoice,
                    'user' => $user,
                    'subscription' => $subscription,
                    'amount' => $amountPaid,
                    'currency' => $currency
                ]
            );



        $options = new Options();

        $options->set(
            'defaultFont',
            'Arial'
        );


        $dompdf = new Dompdf($options);


        $dompdf->loadHtml($html);


        $dompdf->setPaper(
            'A4',
            'portrait'
        );


        $dompdf->render();



        $pdfPath =
            sys_get_temp_dir()
            .
            '/subscription_invoice_' . $invoice->getId() . '.pdf';



        file_put_contents(
            $pdfPath,
            $dompdf->output()
        );





        /*
        |--------------------------------------------------------------------------
        | Email client
        |--------------------------------------------------------------------------
        */


        try {


            $email =
                (new Email())


                    ->from(
                        new Address(
                            'no-reply@falkon.click',
                            'Falkon-ANK Music'
                        )
                    )


                    ->to(
                        $customerEmail
                    )


                    ->subject(
                        'Confirmation abonnement Falkon-ANK Premium'
                    )


                    ->html(
                        $this->renderView(
                            'emails/subscription_success.html.twig',
                            [
                                'user' => $user,
                                'amount' => $amountPaid,
                                'currency' => $currency
                            ]
                        )
                    )


                    ->attachFromPath(
                        $pdfPath,
                        'facture-abonnement.pdf'
                    );



            $mailer->send($email);



        } catch (\Throwable $e) {


            // Logger possible ici

        }




        if (file_exists($pdfPath)) {

            unlink($pdfPath);

        }



        $this->addFlash(
            'success',
            'Votre abonnement mensuel est maintenant actif.'
        );



        return $this->redirectToRoute(
            'app_albums_index'
        );
    }
}