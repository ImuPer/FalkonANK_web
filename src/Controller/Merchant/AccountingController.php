<?php

namespace App\Controller\Merchant;

use App\Entity\Merchant;
use App\Repository\BasketProductRepository;
use App\Repository\ShopRepository;
use App\Service\AccountingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MerchantRepository;

#[Route('/merchant/accounting')]
class AccountingController extends AbstractController
{
    #[Route('/', name: 'merchant_accounting')]
    public function index(
        AccountingService $accountingService,
        MerchantRepository $merchantRepository,
        BasketProductRepository $basketProductRepository,
        ShopRepository $shopRepository
    ): Response {
        $user = $this->getUser();
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
            throw $this->createNotFoundException("Aucun marchand trouvé.");
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
}
