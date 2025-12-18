<?php

namespace App\Controller;

use App\Entity\Shop;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\ProductRepository;
use App\Repository\ShopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class HomePageController extends AbstractController
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route('/shops', name: 'app_shop_all')]
    public function shops(): Response
    {
        return $this->render('home_page/shops.html.twig');
    }

    #[Route('/', name: 'app_home_page')]
    public function index(
        ProductRepository $productRepository,
        CityRepository $cityRepository,
        ShopRepository $shopRepository,
        CategoryRepository $categoryRepository
    ): Response {
        return $this->render('home_page/index.html.twig', [
            'products' => $productRepository->findAll(),
            'cities' => $cityRepository->findAll(),
            'shops' => $shopRepository->findAll(),
            'categories' => $categoryRepository->findAll()
        ]);
    }

    #[Route('/search', name: 'product_search')]
    public function search(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $query = $request->query->get('search', '');
        $products = [];

        if ($query) {
            $products = $productRepository->findByName($query);
        }

        return $this->render('home_page/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'searchQuery' => $query
        ]);
    }

    #[Route('/shop/{id}', name: 'shop_products')]
    public function products(
        int $id,
        ShopRepository $shopRepository,
        CityRepository $cityRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $shop = $shopRepository->find($id);

        if (!$shop) {
            throw $this->createNotFoundException(
                $this->translator->trans('shop.not_found')
            );
        }

        return $this->render('home_page/index.html.twig', [
            'shop' => $shop,
            'products' => $shop->getProducts(),
            'cities' => $cityRepository->findAll(),
            'categories' => $categoryRepository->findAll()
        ]);
    }

    #[Route('/about', name: 'app_about_page')]
    public function aboutPage(): Response
    {
        return $this->render('home_page/about.html.twig');
    }

    #[Route('/category/{name}', name: 'products_by_category')]
    public function productsByCategory(
        string $name,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $products = $productRepository->findByCategoryName($name);

        if (empty($products)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('category.no_products', ['%category%' => $name])
            );
        }

        return $this->render('home_page/index.html.twig', [
            'products' => $products,
            'categoryName' => $name,
            'categories' => $categoryRepository->findAll()
        ]);
    }

    #[Route('/shop/show/{id}', name: 'app_shop_loja', requirements: ['id' => '\d+'])]
    public function shopShow(?Shop $shop): Response
    {
        if (!$shop) {
            throw $this->createNotFoundException(
                $this->translator->trans('shop.not_found')
            );
        }

        return $this->render('home_page/shops.html.twig', [
            'shop_loja' => $shop,
        ]);
    }

    #[Route('/imuhotepu', name: 'app_presentation')]
    public function showAppPage(): Response
    {
        $captures = [
            'screen1.jpg',
            'screen2.jpg',
            'screen3.jpg',
            'screen4.jpg',
            'screen5.jpg',
        ];

        return $this->render('home_page/app_presentation.html.twig', [
            'captures' => $captures,
        ]);
    }
}
