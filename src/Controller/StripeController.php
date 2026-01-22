<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\Order;
use App\Repository\BasketProductRepository;
use App\Repository\BasketRepository;
use App\Repository\CityRepository;
use App\Repository\DeliveryRepository;
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
use Symfony\Contracts\Translation\TranslatorInterface;



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
    public function checkout(Request $request, BasketProductRepository $basketProductRepository, TranslatorInterface $translator): Response
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

        // Vérifie si le montant est inférieur à 1500 escudos CVE (~ 13,6 €)
        if ($totalAmount < 1500) { // 1500 escudos CVE
            $this->addFlash(
        'error',
        $translator->trans('purchase.total_minimum', [
            '%amount%' => 1500,
            '%currency%' => 'CVE',
        ])
    );return $this->redirectToRoute('user_basket');
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

        $deliveryPrice = (float) $request->request->get('delivery_price', 0);

        $deliveryMethod = $request->request->get('delivery_method');
        $session->set('order_info', array_merge(
            $session->get('order_info'),
            [
                'delivery_price' => $deliveryPrice,
                'delivery_method' => $deliveryMethod,
            ]
        ));


        $lineItems[] = [
            'price_data' => [
                'currency' => $_ENV['STRIPE_CURRENCY'],
                'product_data' => [
                    'name' => 'Delivery',
                ],
                'unit_amount' => intval($deliveryPrice * 100),
            ],
            'quantity' => 1,
        ];


        // Générer les URLs de succès et d'annulation avec un placeholder
        $baseUrl = $_ENV['APP_BASE_URL'];

        $checkoutSession = $this->gateway->checkout->sessions->create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $baseUrl . '/success?id_sessions={{CHECKOUT_SESSION_ID}}',
            'cancel_url' => $baseUrl . '/cancel?id_sessions={{CHECKOUT_SESSION_ID}}',
        ]);

        // Rediriger l'utilisateur vers Stripe
        return $this->redirect($checkoutSession->url);
    }




    // ------------success----------------------------------------------------------------------------------
    #[Route('/success', name: 'app_success')]
    public function success(Request $request): Response
    {
        $id = $request->query->get('id_sessions');
        return $this->render('stripe/loading.html.twig', [
            'id_sessions' => $id,
        ]);
    }


    // ------------success FINAL----------------------------------------------------------------------------
    #[Route('/success/final', name: 'app_success_final')]
    public function successFinal(
        Request $request,
        BasketRepository $basketRepository,
        BasketProductRepository $basketProductRepository,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        CityRepository $cityRepository,
        MailerInterface $mailer,
        TranslatorInterface $translator,
        DeliveryRepository $deliveryRepository,
    ): Response {
        // Récupérer l'ID de la session depuis la requête
        $id_sessions = $request->query->get('id_sessions');
        $id_sessions = trim($id_sessions, '{}'); // Nettoie les accolades autour de l'ID

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

        // Vérifier si la commande existe déjà
        $existingOrder = $orderRepository->findOneBy([
            'stripePayId' => $paymentIntentId
        ]);
        if ($existingOrder) {
            $this->addFlash('success', ' Commande déjà enregistrée.');
            return $this->redirectToRoute('app_user_orders');
        }

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
        $deliveryPrice = isset($orderData['delivery_price']) ? (float) $orderData['delivery_price'] : 0;
        $deliveryPrice = $deliveryPrice * 100;
        $deliveryMethod = $orderData['delivery_method'] ?? null;
        $amount = (float) ($amount - $deliveryPrice);

        if (!$orderData || !isset($orderData['city_id'])) {
            return $this->render('stripe/index.html.twig', [
                'status' => 'error',
                'message' => 'A sessão expirou ou está incompleta. Por favor, tente novamente.',
            ]);
        }
        $order->setRef($ref_order);
        $order->setOrderDate(new \DateTime());
        $order->setTotalAmount((float) $orderData['totalAmountSansComission']);//envoier total amonut sans commissions
        $order->setAmountFinal((float) $amount);
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

        // si > 0; creer delevery
        $trackingNumber = $deliveryRepository->generateUniqueTrackingNumber();
        // $deliveryHeader = '';
        // $deliveryBody = '';
        if($deliveryMethod != null){
            $delivery = new Delivery();
            $delivery->setDeliveryMethod($deliveryMethod);
            $delivery->setOrderCustomer($order);
            $delivery->setDeliveryStatus("processing");
            $delivery->setEstimatedDeliveryDate((new \DateTimeImmutable())->modify('+3 days'));
            $delivery->setShippingCost($deliveryPrice);
            // $delivery->setFullAddress($order->getBeneficiaryAddress());

            // Attribution du tracking number UNIQUE
            $delivery->setTrackingNumber($trackingNumber);

            $entityManager->persist($delivery);
            $entityManager->flush();

            //  $deliveryHeader = "
            //     <th style='text-align: left; padding: 8px;'>
            //         {$translator->trans('delivery.title0')}
            //     </th>
            // ";

            // $deliveryBody = "
            //     <td style='padding: 8px; font-size:10px;'>
            //         <strong>{$deliveryPrice} escudos (CVE)</strong>
            //     </td>
            // ";
        }else{
            $deliveryMethod = "Retirar na loja";
        }
        
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

        // initialiser  variables pour recuperer infos do shop
        $shopEmail = "";
        $shopName ="";

        //----------------------------send email to Customer((Message e liste de products Order))-------------------------------------
        // Récupérer l'adresse email du client
        $userName = $user->getFirstName()." ".$user->getLastName();
        $customerEmail = $email ?? $user->getEmail();
        $customerName = $name ?? $userName;

        $amountFormatted = number_format($amount / 100, 2, ',', ' ');
        $cveToEur = 0.00907;
        $cveToUsd = 0.0098;

        $baseUrl = $request->getSchemeAndHttpHost(); // https://site.com

        $productsList = "
            <table style='width: 100%; border-collapse: collapse;'>
                <thead>
                    <tr>
                        <th style='text-align: left; padding: 8px;'>
                            {$translator->trans('products.table.item')}
                        </th>
                        <th style='text-align: left; padding: 8px;'></th>
                        <th style='text-align: left; padding: 8px;'></th>
                        <th style='text-align: left; padding: 8px;'>
                            {$translator->trans('products.table.store')}
                        </th>
                        <th style='text-align: left; padding: 8px;'>
                            {$translator->trans('delivery.title0')}
                        </th>
                    </tr>
                </thead>
                <tbody>
            ";

            $productsListBeneficiary = "
                <table style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr>
                            <th style='text-align: left; padding: 8px;'>Item</th>
                            <th style='text-align: left; padding: 8px;'></th>
                            <th style='text-align: left; padding: 8px;'>Loja</th>
                            <th style='text-align: left; padding: 8px;'>Entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                ";

        foreach ($basketPs as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity();
            $shop = $product->getShop();
            $shopAddress = $shop->getAdress();
            $shopEmail = $shop->getEmail();
            $shopName = $shop->getName();
            $priceCVE = $product->getFinalPrice();
            $priceCVEformatted = number_format($priceCVE / 100, 2, ',', ' ');

            $productsList .= "
                <tr>
                    <td style='padding: 8px;'>
                        <img src='https://falkon.click/upload/images/products/" . rawurlencode($product->getImg()) . "' 
                                    alt='" . htmlspecialchars($product->getName(), ENT_QUOTES, 'UTF-8') . "' 
                                    style='width: 80px; height: auto;'>
                    </td>
                    <td style='padding: 8px;'>{$product->getName()} x{$quantity}</td>
                    <td style='padding: 8px;'>{$priceCVEformatted} CVE</td>
                    <td style='padding: 8px; font-size:10px;'><strong>{$shop}</strong>, {$shopAddress}</td>
                    <td style='padding: 8px; font-size:10px;'><strong>{$deliveryMethod}</strong>, {$deliveryPrice}</td>
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
                    <td style='padding: 8px; font-size:10px;'> <strong>" . htmlspecialchars($shop, ENT_QUOTES, 'UTF-8') . "</strong>, " . htmlspecialchars($shopAddress, ENT_QUOTES, 'UTF-8') . "</td>
                    <td style='padding: 8px; font-size:10px;'> <strong>" . htmlspecialchars($deliveryMethod, ENT_QUOTES, 'UTF-8') . "</strong>,</td>
                </tr>
            ";
        }

        $productsList .= "</tbody></table>";
        $productsListBeneficiary .= "</tbody></table>";



        $beneficiaryName = $orderData['beneficiary_name'];
        $beneficiaryAddress = $orderData['beneficiary_address'];

        $amountEUR = number_format(($amount / 100) * $cveToEur, 2, ',', ' ');
        $amountUSD = number_format(($amount / 100) * $cveToUsd, 2, ',', ' ');

    // $locale = $customerLocale; // ex: 'fr', 'pt', 'en'
    // $translator->setLocale($locale);
    $receiptContent = <<<EOD
    <html>
        <body style="font-family: Arial; font-size: 16px; color: #333;">
            <div style="text-align:center;">
                <img src="https://falkon.click/image/FalkonANK/logo-transparent-png.png" style="max-width:100px;">
            </div>

            <p>{$translator->trans('receipt.greeting', ['%name%' => $customerName])}</p>
            <p>{$translator->trans('receipt.thanks')}</p>

            <p>
                <strong>{$translator->trans('receipt.order_number')}:</strong> {$ref_order}<br>
                <strong>{$translator->trans('receipt.total_amount')}:</strong>
                {$amountFormatted} {$currency}
                (<em>{$amountEUR} €</em> | <em>{$amountUSD} \$</em>)
            </p>

            <p style="color:#a00;">
                <em>{$translator->trans('receipt.notice', ['%beneficiary%' => $beneficiaryName])}</em><br>
                <strong>{$translator->trans('receipt.send_reference', [
                    '%ref%' => $ref_order,
                    '%code%' => $secretCode
                ])}</strong>
            </p>

            <h3>{$translator->trans('receipt.products')}</h3>
            {$productsList}

            <h3>{$translator->trans('receipt.delivery')}</h3>
            <p>
                <strong>{$translator->trans('receipt.name')}:</strong> {$beneficiaryName}<br>
                <strong>{$translator->trans('receipt.address')}:</strong> {$beneficiaryAddress}
            </p>

            <p>{$translator->trans('receipt.signature')}<br>
                <strong>FALKON-ANK Alimentason</strong>
            </p>
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
        $emanilClient->attachFromPath($tempPdfPath, 'reçu-commande.pdf');

        $mailer->send($emailClient);
        unlink($tempPdfPath);


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
            $recapContetB = <<<EOD
            <html>
                <body style="font-family: Arial, sans-serif; font-size: 16px; color: #333;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="https://falkon.click/image/FalkonANK/logo-transparent-png.png" alt="FalkonANK Logo" style="max-width: 100px; height: auto;">
                    </div>          
                    <p>Olá <strong>{$orderData['beneficiary_name']}</strong>,</p>
                    <p>
                        Há uma encomenda para você. Para a retirar, deves comparecer com:<br>
                        • a referência (nº) da encomenda;<br>
                        • o código secreto.<br>
                        Estes dados devem ser solicitados ao cliente ({$userName}) que efetuou a compra.
                    </p>                
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
                ->from(new Address('no-reply@falkonclick.com', 'FalkonANK Alimentason'))
                ->to($beneficiaryEmail)
                ->subject('Recapitulação da entrega')
                ->html($recapContentB);
            // envoier l'email sans pdf
            $mailer->send($emailBenef);
        }

        // ------------envoyer email au Shop----------------------------------------------------------------
        $recapContent = <<<EOD
        <html>
            <body style="font-family: Arial, sans-serif; font-size: 16px; color: #333;">         
                <p>Olá loja: <strong>{$shopName}</strong>,</p>
                <p><strong>Uma nova encomenda:</strong></p>
                <p>A referencia é: <strong>{$ref_order}</strong></p>

                <p style="margin-top: 30px;">
                    Atenciosamente,<br>
                    <strong>FALKON-ANK Alimentason</strong>
                </p>
            </body>
        </html>
        EOD;
        $emailShop = (new Email())
                ->from(new Address('no-reply@falkonclick.com', 'FalkonANK Alimentason'))
                ->to($shopEmail)
                ->subject('Nova encomenda')
                ->html($recapContent);
        // envoier l'email
        $mailer->send($emailShop);


        // Afficher un message de succès
        return $this->render('stripe/index.html.twig', [
            'status' => 'success',
            'customerEmail' => $customerEmail,
            'secrectCode' => $secretCode,
        ]);
    }

    // ------------------------cancel------------------------------------------------------------------------
    #[Route('/cancel', name: 'app_cancel')]
    public function cancel(Request $request): Response
    {
        return $this->render('stripe/index.html.twig', [
            'status' => 'cancelled',
        ]);
    }
}
