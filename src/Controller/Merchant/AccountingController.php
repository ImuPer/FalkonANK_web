<?php

namespace App\Controller\Merchant;

use App\Entity\Merchant;
use App\Entity\Order;
use App\Entity\Shop;
use App\Repository\BasketProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ShopRepository;
use App\Service\AccountingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MerchantRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/merchant/accounting')]
class AccountingController extends AbstractController
{
    #[Route('/', name: 'merchant_accounting')]
    public function index(
        AccountingService $accountingService,
        MerchantRepository $merchantRepository,
        BasketProductRepository $basketProductRepository,
        ShopRepository $shopRepository,
        TranslatorInterface $translator
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login'); // Ou une autre route de ton login
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());


        if ($isAdmin) {
            return $this->render('merchant/accounting/index.html.twig', [
                'isAdmin' => true,
                'monthlyNewRevenue' => null,
                'monthlyRevenue' => $accountingService->getGlobalMonthlyRevenue(),
                'productRevenue' => $accountingService->getGlobalRevenueByProduct(),
                'categoryRevenue' => $accountingService->getGlobalRevenueByCategory(),
                'user' => $user,
            ]);
        }

        $merchant = $merchantRepository->findOneBy(['user' => $user]);

        if (!$merchant) {
            throw $this->createNotFoundException($translator->trans('merchant.not_found'));
        }

        $shop = $shopRepository->findOneBy(['user' => $user]);

        $month = new \DateTime('2025-05-01');
        $revenue = $basketProductRepository->getMonthlyRevenueByShopAndMonth($shop, $month);

        return $this->render('merchant/accounting/index.html.twig', [
            'isAdmin' => false,
            'monthly' => $month,
            'monthlyNewRevenue' => $revenue,
            'monthlyRevenue' => $accountingService->getMonthlyRevenu($merchant),
            'productRevenue' => $accountingService->getRevenueByProduct($merchant),
            'categoryRevenue' => $accountingService->getRevenueByCategory($merchant),
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'orders_shop')]
    public function ordersShop(Shop $shop, AccountingService $accountingService)
    {

        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login'); // Ou une autre route de ton login
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        if ($isAdmin) {
            $isAdmin = true;
        } else {
            $isAdmin = false;
        }
        $ordersfinalizedMontly = $accountingService->getFinalizedOrdersByShopGroupedByMonth($shop->getId());
        $ordersfinalizedWeek = $accountingService->getFinalizedOrdersByShopGroupedByWeek($shop->getId());
        $ordersRembousedOrdersByShop = $accountingService->getRembursedOrdersByShopGroupedByMonth($shop->getId());
        $ordersEmCousoRembousOrdersByShop = $accountingService->getCourRembursOrdersByShopGroupedByMonth($shop->getId());

        $ordersNfinalizedMontly = $accountingService->getNonFinalizedOrdersByShopGroupedByMonth($shop->getId());

        $ordersShopFinalise = $accountingService->getOrdersByShop($shop->getId());
        $ordersShopNfinalise = $accountingService->getNonFinalizedOrdersByShop($shop->getId());
        // dd($ordersRembousedOrdersByShop);

        return $this->render('merchant/accounting/shop_orders.html.twig', [
            'isAdmin' => $isAdmin,
            'ordersWk' => $ordersfinalizedWeek,
            'orders' => $ordersfinalizedMontly,
            'ordersR' => $ordersRembousedOrdersByShop,
            'ordersCR' => $ordersEmCousoRembousOrdersByShop,
            'ordersN' => $ordersNfinalizedMontly,
        ]);
        // }
        //  else {
        //     return $this->redirectToRoute('merchant_accounting');
        // }
    }

    //-----------Recu commende finalsé---------------------------------------------------
    #[Route('/recibo/{id}', name: 'recibo_show')]
    public function show(Order $order, BasketProductRepository $basketProductRepository): Response
    {
        // logique pour afficher le reçu
        $basketProducts = $basketProductRepository->findBasketProductsByOrderId(['order' => $order]);
        // dd($basketProducts);

        return $this->render('merchant/recibo.html.twig', [
            'order' => $order,
            'basketProducts' => $basketProducts,
        ]);
    }

}
