<?php

namespace App\Controller\Admin;

// use App\Controller\Admin\is_granted;
use App\Entity\Ads;
use App\Entity\BasketProduct;
use App\Entity\Category;
use App\Entity\City;
use App\Entity\Contact;
use App\Entity\Merchant;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Shop;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[is_granted('ROLE_MERCHANT')]
#[Route('admin')]

class DashboardController extends AbstractDashboardController
{

    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator
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
        yield MenuItem::linkToRoute('Home', 'fa fa-home', 'app_home_page');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
        // Vérifie le rôle USER
        $user = $this->getUser();
        if ($user->getRoles() === [0 => "ROLE_ADMIN", 1 => "ROLE_USER"]) {

            // 🔽 Contact
            yield MenuItem::linkToCrud('Contactos', 'fas fa-envelope', Contact::class);

            yield MenuItem::subMenu('Users', 'fas fa-users')->setSubItems([
                MenuItem::linkToCrud('Add User', 'fas fa-plus', User::class)->setAction(Crud::PAGE_NEW),
                MenuItem::linkToCrud('Listen Users', 'fas fa-eye', User::class),
            ]);

            yield MenuItem::subMenu('Category', 'fas fa-bars')->setSubItems([
                MenuItem::linkToCrud(
                    'Add Category',
                    'fas fa-plus',
                    Category::class
                )->setAction(Crud::PAGE_NEW),
                MenuItem::linkToCrud(
                    'Listen Category',
                    'fas fa-eye',
                    Category::class
                ),
            ]);
            yield MenuItem::linkToCrud('Produtos', 'fas fa-eye', Product::class);
            yield MenuItem::linkToCrud('City', 'fas fa-city', City::class);
            yield MenuItem::linkToCrud('Merchant request', 'fa-solid fa-shop-lock', Merchant::class);
            yield MenuItem::linkToCrud('Shops', ' fas fa-store fa-2x text-primary', Shop::class);
            yield MenuItem::linkToCrud('BasketProducts', 'fas fa-eye', BasketProduct::class);
            yield MenuItem::linkToCrud('Orders', 'fas fa-shopping-cart', Order::class);

            // 🔽 Lien vers /merchant/accounting
            yield MenuItem::linkToRoute('Contabilidade', 'fas fa-calculator', 'merchant_accounting');
            yield MenuItem::linkToCrud('Ads', 'fas fa-bullhorn', Ads::class);

        } else
            if ($user->getRoles() === [0 => "ROLE_MERCHANT", 1 => "ROLE_USER"] && $user->isMerchant()) {
                yield MenuItem::section('E-Commerce');
                yield MenuItem::section('Catálogos de produtos');

                yield MenuItem::linkToCrud('Produtos', 'fas fa-eye', Product::class);
            
                yield MenuItem::linkToCrud('Minha Loja', ' fas fa-store fa-2x text-primary', Shop::class);

                yield MenuItem::subMenu('Encomendas', 'fas fa-shopping-cart')->setSubItems([
                    MenuItem::linkToCrud(
                    'Ver encomendas',
                    'fas fa-box',
                    Order::class
                    ),
                    MenuItem::linkToCrud(
                    'Produtos das Encomendas',
                    'fas fa-eye',
                    BasketProduct::class
                    ),
                ]);

                // 🔽 Lien vers /merchant/accounting
                yield MenuItem::linkToRoute('Contabilidade', 'fas fa-calculator', 'merchant_accounting');



            } else {
                // Si l'utilisateur n'est pas marchand, rediriger vers la page "Adicionar Loja"
                $this->redirectToRoute('admin', [
                    'crudAction' => 'new',
                    'crudControllerFqcn' => Shop::class,
                ]);
                yield MenuItem::subMenu('Loja', 'fas fa-store fa-2x text-primary')->setSubItems([
                    MenuItem::linkToCrud('Adicionar Loja', 'fas fa-plus', Shop::class)->setAction(Crud::PAGE_NEW),
                    MenuItem::linkToCrud('Ver loja', 'fas fa-eye', Shop::class),
                ]);
            }


    }
}
