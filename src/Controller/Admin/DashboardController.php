<?php

namespace App\Controller\Admin;

// use App\Controller\Admin\is_granted;
use App\Entity\Ads;
use App\Entity\BasketProduct;
use App\Entity\Category;
use App\Entity\City;
use App\Entity\Contact;
use App\Entity\Delivery;
use App\Entity\Merchant;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Shop;
use App\Entity\User;
use App\Repository\ContactRepository;
use App\Repository\MerchantRepository;
use App\Repository\OrderRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[is_granted('ROLE_MERCHANT')]
#[Route('admin')]

class DashboardController extends AbstractDashboardController
{

    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private MerchantRepository $merchantRepository,
        private ContactRepository $contactRepository,
        private OrderRepository $orderRepository,
        private TranslatorInterface $translator
    ) {
    }
    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        $url = "";
        $user = $this->getUser();
        if ($user->getRoles() === [0 => "ROLE_ADMIN", 1 => "ROLE_USER"] || ($user->getRoles() === [0 => "ROLE_MERCHANT", 1 => "ROLE_USER"] && $user->isMerchant())) {
            $url = $this->adminUrlGenerator
                ->setController(ProductCrudController::class)
                ->generateUrl();
        } else {
            $url = $this->adminUrlGenerator
                ->setController(ShopCrudController::class)
                ->generateUrl();
        }

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/image/FalkonANK/logo-transparent-png.png" alt="FalkonANK" style="max-height: 40px; vertical-align: middle;"> <span style="margin-left:10px;">FalkonANK</span>');
    }



    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addJsFile('build/refund_toggle.js');
    }



    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute($this->translator->trans('menu.home'), 'fa fa-home', 'app_home_page');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
        // Vérifie le rôle USER
        $user = $this->getUser();
        if ($user->getRoles() === [0 => "ROLE_ADMIN", 1 => "ROLE_USER"]) {

            // 🔽 Contact
            // yield MenuItem::linkToCrud('Contactos', 'fas fa-envelope', Contact::class);
            $Count_c = $this->contactRepository->countContactsWithoutResponse();
            yield MenuItem::linkToCrud(
                $this->translator->trans('menu.contacts'),
                'fas fa-envelope',
                Contact::class
            )->setBadge($Count_c > 0 ? (string) $Count_c : null, 'danger');

            yield MenuItem::subMenu($this->translator->trans('menu.users'), 'fas fa-users')->setSubItems([
                MenuItem::linkToCrud($this->translator->trans('menu.user.add'), 'fas fa-plus', User::class)->setAction(Crud::PAGE_NEW),
                MenuItem::linkToCrud($this->translator->trans('menu.user.list'), 'fas fa-eye', User::class),
            ]);

            yield MenuItem::subMenu($this->translator->trans('menu.category'), 'fas fa-bars')->setSubItems([
                MenuItem::linkToCrud(
                    $this->translator->trans('menu.categorys.add'),
                    'fas fa-plus',
                    Category::class
                )->setAction(Crud::PAGE_NEW),
                MenuItem::linkToCrud(
                    $this->translator->trans('menu.categorys.view'),
                    'fas fa-eye',
                    Category::class
                ),
            ]);
            yield MenuItem::linkToCrud($this->translator->trans('menu.products'), 'fas fa-eye', Product::class);
            yield MenuItem::linkToCrud('City', 'fas fa-city', City::class);

            $pendingCount = $this->merchantRepository->countPendingMerchants();
            yield MenuItem::linkToCrud(
                'Merchant request',
                'fa-solid fa-shop-lock',
                Merchant::class
            )->setBadge($pendingCount > 0 ? (string) $pendingCount : null, 'danger');

            yield MenuItem::linkToCrud('Shops', ' fas fa-store fa-2x text-primary', Shop::class);
            yield MenuItem::linkToCrud('BasketProducts', 'fas fa-eye', BasketProduct::class);

            // Menu principal "Orders"
            // Compteur des commandes "Reembolso" en cours
            $countRefundInProgress = $this->orderRepository->countRefundInProgress();
            yield MenuItem::subMenu($this->translator->trans('menu.orders0'), 'fas fa-shopping-cart')->setSubItems([
                    // Sous-menu "Reembolso" à l'intérieur d'Orders
                MenuItem::linkToUrl(
                    $this->translator->trans('menu.orders.refund'),
                    'fa-solid fa-shop-lock',
                    $this->adminUrlGenerator
                        ->setController(OrderCrudController::class)
                        ->setAction(Crud::PAGE_INDEX)
                        ->set('filter', 'reembolso')
                        ->generateUrl()
                )->setBadge($countRefundInProgress > 0 ? (string) $countRefundInProgress : null, 'danger'),
                MenuItem::linkToCrud(
                        $this->translator->trans('menu.orders.all'),
                        'fas fa-eye',
                        Order::class
                    ),
            ])->setBadge($countRefundInProgress > 0 ? (string) $countRefundInProgress : null, 'danger');
            
            yield MenuItem::linkToCrud('Delivery', 'fas fa-truck', entityFqcn: Delivery::class);
            // 🔽 Lien vers /merchant/accounting
            yield MenuItem::linkToRoute('Contabilidade', 'fas fa-calculator', 'merchant_accounting');
            yield MenuItem::linkToCrud('Ads', 'fas fa-bullhorn', Ads::class);

        } else
            if ($user->getRoles() === [0 => "ROLE_MERCHANT", 1 => "ROLE_USER"] && $user->isMerchant()) {
                yield MenuItem::section('E-Commerce');
                yield MenuItem::section('Catálogos de produtos');

                yield MenuItem::linkToCrud($this->translator->trans('menu.shop.my'), ' fas fa-store fa-2x text-primary', Shop::class);
                yield MenuItem::linkToCrud($this->translator->trans('menu.products'), 'fas fa-eye', Product::class);

                $merchant = $this->getUser();
                $Count_o = $this->orderRepository->countPendingOrMissingSecretByMerchant($merchant);
                $countOrderInProgress = $this->orderRepository->countOrderInProgressByMerchant($merchant);
                $countRefundInProgress = $this->orderRepository->countRefundInProgressByMerchant($merchant);
                $countRefundFinish = $this->orderRepository->countRefundFinishByMerchant($merchant);
                yield MenuItem::subMenu($this->translator->trans('menu.orders0'), 'fas fa-shopping-cart')->setSubItems([
                    MenuItem::linkToUrl(
                        $this->translator->trans('menu.orders.new'),
                        'fa-solid fa-shop-lock',
                        $this->adminUrlGenerator
                            ->setController(OrderCrudController::class)
                            ->setAction(Crud::PAGE_INDEX)
                            ->set('filter', 'Em processamento')
                            ->generateUrl()
                    )
                        ->setBadge($countOrderInProgress > 0 ? (string) $countOrderInProgress : null, 'danger'),
                    MenuItem::linkToUrl(
                        $this->translator->trans('menu.orders.refund'),
                        'fa-solid fa-shop-lock',
                        $this->adminUrlGenerator
                            ->setController(OrderCrudController::class)
                            ->setAction(Crud::PAGE_INDEX)
                            ->set('filter', 'reembolso')
                            ->generateUrl()
                    )
                        ->setBadge($countRefundInProgress > 0 ? (string) $countRefundInProgress : null, 'warning'),
                    MenuItem::linkToUrl(
                        $this->translator->trans('menu.orders.refunded'),
                        'fa-solid fa-shop-lock',
                        $this->adminUrlGenerator
                            ->setController(OrderCrudController::class)
                            ->setAction(Crud::PAGE_INDEX)
                            ->set('filter', 'Reembolsado')
                            ->generateUrl()
                    )
                        ->setBadge($countRefundFinish > 0 ? (string) $countRefundFinish : null, 'refund'),
                    MenuItem::linkToCrud(
                        $this->translator->trans('menu.orders.all'),
                        'fas fa-eye',
                        Order::class
                    ),
                    MenuItem::linkToCrud(
                        $this->translator->trans('menu.orders.products'),
                        'fas fa-eye',
                        BasketProduct::class
                    ),
                ])->setBadge($Count_o > 0 ? (string) $Count_o : null, 'danger');


                // 🔽 Lien vers /merchant/accounting
                yield MenuItem::linkToRoute($this->translator->trans('menu.accounting'), 'fas fa-calculator', 'merchant_accounting');



            } else {
                // Si l'utilisateur n'est pas marchand, rediriger vers la page "Adicionar Loja"
                $this->redirectToRoute('admin', [
                    'crudAction' => 'new',
                    'crudControllerFqcn' => Shop::class,
                ]);
                yield MenuItem::subMenu($this->translator->trans('menu.shop'), 'fas fa-store fa-2x text-primary')->setSubItems([
                    MenuItem::linkToCrud($this->translator->trans('menu.shop.add'), 'fas fa-plus', Shop::class)->setAction(Crud::PAGE_NEW),
                    MenuItem::linkToCrud($this->translator->trans('menu.shop.view'), 'fas fa-eye', Shop::class),
                ]);
            }


    }
}
