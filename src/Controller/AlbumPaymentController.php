<?php

namespace App\Controller;

use App\Entity\AlbumPurchase;
use App\Repository\AlbumRepository;
use App\Repository\AlbumPurchaseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

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
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
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
        $userId = $session->metadata->user_id;

        $user = $userRepository->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }

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

            $purchase->setInvoiceNumber(
                sprintf(
                    'ALB-%s-%06d',
                    date('Y'),
                    $purchase->getId()
                )
            );
            $em->flush();

            //----------facture----------------------------------------------------------
            $customerName = $session->customer_details->name ?? '';
            $customerEmail = $session->customer_details->email ?? '';
            $customerPhone = $session->customer_details->phone ?? '';
            $billingAddress = $session->customer_details->address ?? null;
            $customerAddressLine1 = $billingAddress?->line1 ?? '';
            $customerAddressLine2 = $billingAddress?->line2 ?? '';
            $customerCity = $billingAddress?->city ?? '';
            $customerPostalCode = $billingAddress?->postal_code ?? '';
            $customerCountry = $billingAddress?->country ?? '';

            $amountPaid = $session->amount_total / 100;
            $currency = strtoupper($session->currency);
            $paymentIntent = $session->payment_intent;

            $invoiceData = [
                'invoiceNumber' => sprintf(
                    'ALB-%s-%06d',
                    date('Y'),
                    $purchase->getId()
                ),

                'purchaseDate' => $purchase->getPurchaseDate(),

                'userFirstName' => $user->getFirstName(),
                'userLastName' => $user->getLastName(),
                'userEmail' => $user->getEmail(),

                'customerName' => $customerName,
                'customerEmail' => $customerEmail,
                'customerPhone' => $customerPhone,

                'albumName' => $album->getName(),
                'recordLabel' => $album->getRecordLabel(),
                'releaseDate' => $album->getReleaseDate(),

                'amountPaid' => $amountPaid,
                'currency' => $currency,
                'paymentIntent' => $paymentIntent,

                'customerAddressLine1' => $customerAddressLine1,
                'customerAddressLine2' => $customerAddressLine2,
                'customerCity' => $customerCity,
                'customerPostalCode' => $customerPostalCode,
                'customerCountry' => $customerCountry,
            ];

            $html = $this->renderView(
                'invoice/album_invoice.html.twig',
                $invoiceData
            );

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);

            $dompdf->setPaper('A4', 'portrait');

            $dompdf->render();

            $pdfContent = $dompdf->output();

            $pdfPath = sys_get_temp_dir() .
                '/album_invoice_' .
                $purchase->getId() .
                '.pdf';

            file_put_contents(
                $pdfPath,
                $pdfContent
            );

            // envoi email-----------------------------------------
            $emailRecipient = $customerEmail ?: $user->getEmail();
            $email = (new Email())
                ->from(
                    new Address(
                        'no-reply@votresite.com',
                        'Music Store'
                    )
                )
                ->to($emailRecipient)
                ->subject('Facture achat album')
                ->html(
                    $this->renderView(
                        'emails/album_purchase.html.twig',
                        [
                            'customerName' => $customerName,
                            'userFirstName' => $user->getFirstName(),

                            'albumName' => $album->getName(),
                            'amountPaid' => $amountPaid,
                            'currency' => $currency,

                            'customerAddressLine1' => $customerAddressLine1,
                            'customerAddressLine2' => $customerAddressLine2,
                            'customerCity' => $customerCity,
                            'customerPostalCode' => $customerPostalCode,
                            'customerCountry' => $customerCountry,
                        ]
                    )
                )
                ->attachFromPath(
                    $pdfPath,
                    'facture-album-' . $purchase->getId() . '.pdf'
                );

            try {

                $mailer->send($email);

            } catch (\Throwable $e) {

                // logger l'erreur
            } finally {
                //netoyer-----------------------
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
            }

        }


        $this->addFlash('success', 'Album acheté avec succès 🎵');

        return $this->redirectToRoute('app_music_by_album', [
            'id' => $album->getId()
        ]);
    }

    #[Route('/invoice/{id}/download', name: 'app_invoice_download')]
    public function downloadInvoice(
        AlbumPurchase $purchase
    ): Response {
        if ($purchase->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $html = $this->renderView(
            'music/album_invoice.html.twig',
            [
                'purchase' => $purchase,
                'user' => $purchase->getUser(),
                'album' => $purchase->getAlbum(),
            ]
        );

        $options = new Options();
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'attachment; filename="facture-' .
                    $purchase->getInvoiceNumber() .
                    '.pdf"'
            ]
        );
    }
}