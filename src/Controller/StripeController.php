<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\BasketProductRepository;
use App\Repository\BasketRepository;
use App\Repository\CityRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;



class StripeController extends AbstractController
{
    private $manager;
    private $gateway;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
        $this->gateway = new StripeClient($_ENV['STRIPE_SECRETKEY']);
    }

    //--------------checkout--------------------------------------------------
    #[Route('/checkout', name: 'app_checkout', methods: ['POST'])]
    public function checkout(Request $request, BasketProductRepository $basketProductRepository): Response
    {
        $basket = $this->getUser()->getBasket();
        $basketProducts = $basketProductRepository->findBasketProductsByBasketId($basket);
        $lineItems = [];

        // Calcul du montant total
        $totalAmount = 0;
        $totalAmountSansComission = 0;
        foreach ($basketProducts as $bp) {
            $totalAmount += $bp->getProduct()->getFinalPrice() * $bp->getQuantity();
            $totalAmountSansComission += $bp->getProduct()->getPrice() * $bp->getQuantity();
        }
        $totalAmount = $totalAmount / 100;
        $totalAmountSansComission = (float) $totalAmountSansComission;

        // Vérifie si le montant est inférieur à 0,150 €
        if ($totalAmount < 250) { // 250 centimes en centimes
            $this->addFlash('error', "O valor total da compra deve ser superior a 250 CVE(escudos).");
            return $this->redirectToRoute('user_basket');
        }

        // Vérifie que tous les produits appartiennent au même shop
        $firstShopId = $basketProducts[0]->getProduct()->getShop()->getId();
        foreach ($basketProducts as $bp) {
            $currentShopId = $bp->getProduct()->getShop()->getId();

            if ($currentShopId !== $firstShopId) {
                $this->addFlash('error', "Todos os produtos na Cesta devem pertencer à mesma loja. Remova os produtos de lojas diferentes.");
                return $this->redirectToRoute('user_basket');
            }
        }


        // Récupérer les infos du formulaire
        $cityId = $request->request->get('city_id'); // 👈 récupère l'id sélectionné
        $beneficiaryName = $request->request->get('beneficiary_name');
        $deliveryAddress = $request->request->get('beneficiary_address');
        $beneficiary_email = $request->request->get('beneficiary_email');
        $phone = $request->request->get('phone');

        // Vérification que tous les produits sont dans la même ville que celle sélectionnée
        foreach ($basketProducts as $bp) {
            $productCityId = $bp->getProduct()->getShop()->getCity()->getId();

            if ($productCityId != $cityId) {
                $this->addFlash('error', "Todos os produtos na Cesta devem pertencer à cidade selecionada para o Beneficiário. Remova os produtos de outra cidade ou altere a cidade do Beneficiário.");
                return $this->redirectToRoute('user_basket'); // Rediriger vers la page panier ou une autre page pertinente
            }
        }

        // Enregistrer en session pour les récupérer après paiement
        $session = $request->getSession();
        $session->set('order_info', [
            'city_id' => $cityId,
            'beneficiary_name' => $beneficiaryName,
            'beneficiary_address' => $deliveryAddress,
            'beneficiary_email' => $beneficiary_email,
            'phone' => $phone,
            'totalAmountSansComission' => $totalAmountSansComission,
        ]);



        foreach ($basketProducts as $bp) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $_ENV['STRIPE_CURRENCY'],
                    'product_data' => [
                        'name' => $bp->getProduct()->getName(),
                        'images' => [
                            $request->getSchemeAndHttpHost() . '/upload/images/products/' . rawurlencode($bp->getProduct()->getImg()),
                        ],
                    ],
                    'unit_amount' => intval($bp->getProduct()->getFinalPrice()), // Montant en centimes
                ],
                'quantity' => $bp->getQuantity(),
            ];
        }

        // Générer les URLs de succès et d'annulation avec un placeholder
        $checkoutSession = $this->gateway->checkout->sessions->create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_success', [
                'id_sessions' => '{CHECKOUT_SESSION_ID}'
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_cancel', [
                'id_sessions' => '{CHECKOUT_SESSION_ID}'
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        // Rediriger l'utilisateur vers Stripe
        return $this->redirect($checkoutSession->url);
    }


    // ------------success--------------------
    #[Route('/success', name: 'app_success')]
    public function success(
        Request $request,
        BasketRepository $basketRepository,
        BasketProductRepository $basketProductRepository,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        CityRepository $cityRepository,
        MailerInterface $mailer
    ): Response {
        // Récupérer l'ID de la session depuis la requête
        $id_sessions = $request->query->get('id_sessions');
        // dd($id_sessions);
        if (!$id_sessions) {

            // Si l'ID de session est manquant ou invalide, afficher une erreur
            return $this->render('stripe/index.html.twig', [
                'status' => 'error',
                'message' => 'ID de session invalide ou manquant',
            ]);
        }

        try {
            // Récupérer les informations de la session Stripe
            $customer = $this->gateway->checkout->sessions->retrieve($id_sessions);
        } catch (\Exception $e) {
            // Si l'ID de session est invalide ou l'appel échoue, capturer l'exception et afficher l'erreur
            return $this->render('stripe/index.html.twig', [
                'status' => 'error',
                'message' => 'Erreur de récupération des informations de la session Stripe: ' . $e->getMessage(),
            ]);
        }

        // Si l'appel est réussi, traiter la commande comme d'habitude
        $timezone = date_default_timezone_get();
        $name = $customer->customer_details->name;
        $email = $customer->customer_details->email;
        $payment_status = $customer->payment_status;
        $amount = $customer->amount_total;
        $currency = $customer->currency;

        // récupérer l'ID du paiement associé (payment_intent)
        $paymentIntentId = $customer->payment_intent;

        $paymentIntent = $this->gateway->paymentIntents->retrieve($paymentIntentId);
        $timestamp = $paymentIntent->created; // UNIX timestamp
        $paymentDate = (new \DateTime())->setTimestamp($timestamp);



        // Logique pour enregistrer la commande et les produits
        $user = $this->getUser();
        $basket = $basketRepository->findOneBy(['user' => $user]);
        $basketPs = $basketProductRepository->findBasketProductsByBasketId($basket);

        //
        $userId = $this->getUser()->getId();
        $secretCode = $orderRepository->generateUniqueOrderSecretCode($userId);

        // Créer une nouvelle commande
        $order = new Order();
        $order->setBasket($basket);
        // Initialiser le numéro de commande
        $ref_order = 'ClientNr01';
        function generateCustomRefOrder()
        {
            $lettersDigits = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $digits = '0123456789';

            $part1 = '';
            for ($i = 0; $i < 3; $i++) {
                $part1 .= $lettersDigits[rand(0, strlen($lettersDigits) - 1)];
            }

            $part2 = '';
            for ($i = 0; $i < 3; $i++) {
                $part2 .= $digits[rand(0, strlen($digits) - 1)];
            }

            $part3 = '';
            for ($i = 0; $i < 4; $i++) {
                $part3 .= $lettersDigits[rand(0, strlen($lettersDigits) - 1)];
            }

            return $part1 . '-' . $part2 . '-' . $part3;
        }

        do {
            $ref_order = $userId . "-" . generateCustomRefOrder();
        } while ($orderRepository->refOrderExists($ref_order));

        //infos de beneficiario
        $orderData = $request->getSession()->get('order_info');

        if (!$orderData || !isset($orderData['city_id'])) {
            return $this->render('stripe/index.html.twig', [
                'status' => 'error',
                'message' => 'A sessão expirou ou está incompleta. Por favor, tente novamente.',
            ]);
        }
        $order->setRef($ref_order);
        $order->setOrderDate(new \DateTime());
        $order->setTotalAmount((float) $orderData['totalAmountSansComission']);//envoier total amonut sans commissions
        $order->setAmountFinal($amount);
        $order->setOrderStatus("Em processamento");
        $order->setRefund(false);

        $cityId = $request->getSession()->get('order_info')['city_id'];
        $city = $cityRepository->find($cityId); // 👈 convertit l'ID en objet City

        $order->setCityBeneficiary($city);
        $order->setBeneficiaryName($orderData['beneficiary_name']);
        $order->setBeneficiaryAddress($orderData['beneficiary_address']);
        if ($orderData['beneficiary_email']) {
            $order->setBeneficiaryEmail($orderData['beneficiary_email']);
        }
        $order->setPhone($orderData['phone']);
        $order->setAutoSecretCode($secretCode);
        $order->setStripePayId($paymentIntentId);
        $entityManager->persist($order);
        $entityManager->flush();

        // Mettre à jour les produits du panier
        foreach ($basketPs as $basketP) {
            $basketP->setPayment(true);
            $basketP->setDatePay($paymentDate);
            $basketP->setPaymentMethod("Carta Bancario");
            $basketP->setPaymentStatus($payment_status);
            $basketP->setOrderC($order);

            $product = $basketP->getProduct();
            $stock = $product->getStock();
            $quantityBp = $basketP->getQuantity();
            $product->setStock($stock - $quantityBp);
            $entityManager->flush();
        }


        //----------------------------send email to Customer((Message e liste de products Order))-------------------------------------
        // Récupérer l'adresse email du client
        $customerEmail = $email ?? $user->getEmail();
        $customerName = $name ?? $user->getUsername();

        $amountFormatted = number_format($amount / 100, 2, ',', ' ');
        $cveToEur = 0.00907;
        $cveToUsd = 0.0098;

        $baseUrl = $request->getSchemeAndHttpHost(); // https://tonsite.com

        $productsList = "
<table style='width: 100%; border-collapse: collapse;'>
    <thead>
        <tr>
            <th style='text-align: left; padding: 8px;'>Imagem</th>
            <th style='text-align: left; padding: 8px;'>Produto</th>
            <th style='text-align: left; padding: 8px;'>Preço</th>
            <th style='text-align: left; padding: 8px;'>Loja</th>
        </tr>
    </thead>
    <tbody>
";

        $productsListBeneficiary = "
<table style='width: 100%; border-collapse: collapse;'>
    <thead>
        <tr>
            <th style='text-align: left; padding: 8px;'>Imagem</th>
            <th style='text-align: left; padding: 8px;'>Produto</th>
            <th style='text-align: left; padding: 8px;'>Loja</th>
        </tr>
    </thead>
    <tbody>
";

        foreach ($basketPs as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity();
            $shop = $product->getShop();
            $shopAddress = $shop->getAdress();
            $priceCVE = $product->getFinalPrice();
            $priceCVEformatted = number_format($priceCVE / 100, 2, ',', ' ');

            $imageFilename = $product->getImg();
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/upload/images/products/' . $imageFilename;

            if (file_exists($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                $imageMime = mime_content_type($imagePath);
                $imageBase64 = 'data:' . $imageMime . ';base64,' . $imageData;
            } else {
                $imageBase64 = ''; // ou une image par défaut
            }

            $productsList .= "
        <tr>
            <td style='padding: 8px;'><img src='{$imageBase64}' alt='{$product->getName()}' style='width: 80px; height: auto;'></td>
            <td style='padding: 8px;'>{$product->getName()} x{$quantity}</td>
            <td style='padding: 8px;'>{$priceCVEformatted} CVE</td>
            <td style='padding: 8px;'>{$shop}, {$shopAddress}</td>
        </tr>
    ";

            $productsListBeneficiary .= "
                <tr>
                    <td style='padding: 8px;'>
                        <img src='https://falkon.click/upload/images/products/" . rawurlencode($product->getImg()) . "' 
                            alt='" . htmlspecialchars($product->getName(), ENT_QUOTES, 'UTF-8') . "' 
                            style='width: 80px; height: auto;'>
                    </td>
                    <td style='padding: 8px;'>" . htmlspecialchars($product->getName(), ENT_QUOTES, 'UTF-8') . " x{$quantity}</td>
                    <td style='padding: 8px;'>" . htmlspecialchars($shop, ENT_QUOTES, 'UTF-8') . ", " . htmlspecialchars($shopAddress, ENT_QUOTES, 'UTF-8') . "</td>
                </tr>
            ";
        }

        $productsList .= "</tbody></table>";
        $productsListBeneficiary .= "</tbody></table>";



        $beneficiaryName = $orderData['beneficiary_name'];
        $beneficiaryAddress = $orderData['beneficiary_address'];

        $amountEUR = number_format(($amount / 100) * $cveToEur, 2, ',', ' ');
        $amountUSD = number_format(($amount / 100) * $cveToUsd, 2, ',', ' ');





        $receiptContent = <<<EOD
<html>
  <body style="font-family: Arial, sans-serif; font-size: 16px; color: #333;">
    <div style="text-align: center; margin-bottom: 20px;">
      <img src="https://falkon.click/image/FalkonANK/logo-transparent-png.png" alt="FalkonANK Logo" style="max-width: 200px; height: auto;">
    </div>

    <p>Olá <strong>{$customerName}</strong>,</p>

    <p>Obrigado pela sua encomenda. Aqui está o resumo da sua compra:</p>

    <p>
      <strong>Número da encomenda:</strong> {$ref_order}<br>
      <strong>Valor total:</strong> {$amountFormatted} {$currency}
      (<em>{$amountEUR} €</em> | <em>{$amountUSD} \$</em>)
    </p>

    <p style="color: #a00;">
      <em>O beneficiário deverá apresentar seu documento de identidade, referência da encomenda e o codigo secreto. </em>
      <strong>Deves envia-lo essa referência : {$ref_order}</strong> e o <strong>codigo secreto : {$secretCode}</strong>
    </p>
    <br>

    <div style="text-align: center;">
        <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px;">Produtos:</h3>
        {$productsList}
    </div>

    <h3>Entrega para:</h3>
    <p>
      <strong>Nome:</strong> {$beneficiaryName}<br>
      <strong>Endereço:</strong> {$beneficiaryAddress}
    </p>

    <p style="margin-top: 30px;">Atenciosamente,<br>
    <strong>FALKON-ANK Alimentason</strong></p>

  </body>
</html>
EOD;

        // Envoi du mail au client
        $emailClient = (new Email())
            ->from(new Address('no-reply@FalkonANK.com', 'FalkonANK Alimentason'))
            ->to($customerEmail)
            ->subject('Votre reçu de commande')
            ->html($receiptContent); // ✅ maintenant en HTML avec images

        //-------Reçu d'achat--------------------
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);         // ✅ Active le support HTML5 (important)
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($receiptContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Enregistrement temporaire
        $pdfOutput = $dompdf->output();
        $tempPdfPath = sys_get_temp_dir() . '/receipt_' . uniqid() . '.pdf';
        file_put_contents($tempPdfPath, $pdfOutput);

        // Attacher le fichier PDF à l'e-mail
        $emailClient->attachFromPath($tempPdfPath, 'reçu-commande.pdf');

        $mailer->send($emailClient);
        unlink($tempPdfPath);
        //----------------------------------------------------------------------------------------------------------------



        //------------send email to benef.(Message e liste de products Order)---------------------------------------------------

        $beneficiaryEmail = $orderData['beneficiary_email'] ?? null;

        // Será entregue para você neste endereço:
        //     {$orderData['beneficiary_address']}
        // <p>Um pacote será entregue para você neste endereço:</p>
        // <p>
        //   <strong>Endereço:</strong> {$orderData['beneficiary_address']}
        // </p>
        // dd($productsList);
        if ($beneficiaryEmail) {
            $recapContent = <<<EOD
        <html>
          <body style="font-family: Arial, sans-serif; font-size: 16px; color: #333;">
             <div style="text-align: center; margin-bottom: 20px;">
                <img src="https://falkon.click/image/FalkonANK/logo-transparent-png.png" alt="FalkonANK Logo" style="max-width: 200px; height: auto;">
            </div>
          
            <p>Olá <strong>{$orderData['beneficiary_name']}</strong>,</p>
        
            <p>Uma encomenda para voçe:</p>
               
            <div style="text-align: center;">
                <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px;">Produtos:</h3>
                {$productsListBeneficiary}
            </div>
                
            <p style="margin-top: 30px;">Atenciosamente,<br>
            <strong>FALKON-ANK Alimentason</strong></p>

          </body>
        </html>
        EOD;

            $emailBenef = (new Email())
                ->from(new Address('no-reply@tonsite.com', 'FalkonANK Alimentason'))
                ->to($beneficiaryEmail)
                ->subject('Récapitulatif de votre entrega')
                ->html($recapContent);

            //------Lista de artigos--pdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);         // ✅ Active le support HTML5 (important)
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($recapContent);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Enregistrement temporaire
            $pdfOutput = $dompdf->output();
            $tempPdfPath = sys_get_temp_dir() . '/receipt_' . uniqid() . '.pdf';
            file_put_contents($tempPdfPath, $pdfOutput);

            // Attacher le fichier PDF à l'e-mail
            $emailBenef->attachFromPath($tempPdfPath, 'lista_artigos.pdf');


            try {
                $mailer->send($emailBenef);
            } catch (\Exception $e) {
                dump("Erro ao enviar email ao beneficiário: " . $e->getMessage());
            }

            // $mailer->send($emailBenef);
        }




        // ----------------------------------------------------------------------------------------------------------------

        // Afficher un message de succès
        return $this->render('stripe/index.html.twig', [
            'status' => 'success',
            'customerEmail' => $customerEmail,
        ]);
    }

    // ------------cancel--------------------
    #[Route('/cancel', name: 'app_cancel')]
    public function cancel(Request $request): Response
    {
        dd($request->query->all());
        return $this->render('stripe/index.html.twig', [
            'status' => 'cancelled',
        ]);
    }
}
