<?php

namespace App\Controller;

use App\Entity\Basket;
use App\Entity\BasketProduct;
use App\Entity\Product;
use App\Entity\City;
use App\Form\BasketType;
use App\Repository\BasketProductRepository;
use App\Repository\BasketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/basket')]
class BasketController extends AbstractController
{

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    // delite BasketProdut--------------------------------------------------------
    #[Route('/{id}', name: 'app_basketP_delete', methods: ['POST'])]
    public function delete(Request $request, BasketProduct $basketProduct, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $basketProduct->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($basketProduct);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_basket', [], Response::HTTP_SEE_OTHER);
    }
    // Fin de delite BasketProdut--------------------------------------------------------


    //--------------Delite products----------------------------------------
    #[Route('/basket/{id}/delete', name: 'app_removePB_delite', methods: ['POST'])]
    public function removeProductAction(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        BasketProductRepository $basketProductRepository,
        BasketRepository $basketRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        $user = $this->getUser();

        if ($user) {
            // Valider le token CSRF
            $submittedToken = $request->headers->get('X-CSRF-TOKEN');
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('update_quantity', $submittedToken))) {
                return new JsonResponse(['success' => false, 'message' => $this->translator->trans('basket.invalid_csrf')], 403);
            }

            // Récupérer le produit du panier par son ID
            $basketProduct = $basketProductRepository->find($id);
            if (!$basketProduct) {
                return new JsonResponse(['success' => false, 'message' => $this->translator->trans('basket.product_not_found')], 404);
            }

            // Récupérer le panier de l'utilisateur
            $basket = $basketRepository->findOneBy(['user' => $user]);
            if ($basket && $basket->getBasketProducts()->contains($basketProduct)) {
                // Supprimer le produit du panier
                $basket->removeBasketProduct($basketProduct);
                $entityManager->remove($basketProduct);
                $entityManager->flush();

                return new JsonResponse(['success' => true]);
            }
        }

        // Si l'utilisateur n'est pas connecté, retourner une réponse JSON
        return new JsonResponse(['success' => false, 'message' => $this->translator->trans('basket.user_not_authenticated')], 403);
    }
    //--------------Fin de delite products----------------------------------------



    // --------------------------------Edit quantity--------------------------------
    #[Route('/basket/edit/{id}/{quantity}', name: 'app_quantity_edit', methods: ['POST'])]
    public function editQuantity(int $id, int $quantity, EntityManagerInterface $entityManager, BasketProductRepository $basketProductRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('basket.user_not_authenticated')], 403);
        }

        $basketProduct = $basketProductRepository->find($id);
        if (!$basketProduct) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('basket.empty')], 404);
        }

        if ($quantity === 0) {
            $entityManager->remove($basketProduct);
            $entityManager->flush();
            return new JsonResponse(['success' => true, 'message' => 'Product removed']);
        } else {
            $basketProduct->setQuantity($quantity);
            $entityManager->flush();
            return new JsonResponse(['success' => true, 'message' => 'Quantity updated']);
        }
    }
    // ---------------------Fin de edit quantity-----------------------------------------------




    // ----------add products --------------------------------
    #[Route('/{id}', name: 'app_add', methods: ['GET'])]

    public function addProduct(
        Product $product,
        BasketRepository $basketRepository,
        EntityManagerInterface $entityManager,
        BasketProductRepository $basketProductRepository
    ): Response {
        $user = $this->getUser();
        if ($user) {
            $basket = $basketRepository->findOneBy(['user' => $user]);

            if (!$basket) {
                $basket = new Basket();
                $basket->setUser($user);
                $entityManager->persist($basket);
                $entityManager->flush();
            }

            $basketProduct = $basketProductRepository->showBasketAndProduct($basket, $product);
            if ($basketProduct) {
                $basketProduct->setQuantity($basketProduct->getQuantity() + 1);
                $entityManager->persist($basketProduct);
            } else {
                $basketProduct = new BasketProduct();
                $basketProduct->setBasket($basket);
                $basketProduct->setProduct($product);
                $basketProduct->setQuantity(1);
                $basketProduct->setPayment(false);
                $basket->addBasketProduct($basketProduct);
                $entityManager->persist($basketProduct);
                $entityManager->persist($basket);
            }

            $entityManager->flush();
            return $this->redirectToRoute('user_basket');
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    //--------------Fin d'add products----------------------------------------



    // ------------------Show user basket --------------------------------
    #[Route('/', name: 'user_basket', methods: ['GET'])]
    public function showUserBasket(BasketRepository $basketRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur actuellement connecté

        if ($user) {
            // Récupère le panier de l'utilisateur
            $basket = $basketRepository->findOneBy(['user' => $user]);
            if ($basket) {
                // Récupérer tous les produits du panier
                $basketProducts = $basket->getBasketProducts();

                // Filtrer les produits du panier avec 'payment' = false
                $basketProducts = $basketProducts->filter(function ($basketProduct) {
                    return !$basketProduct->isPayment();
                });
                $message = "";
                if ($basketProducts->isEmpty()) {
                    $message = "A sua cesta está vazia !";
                }
                $cities = $entityManager->getRepository(City::class)->findAll();
                return $this->render('basket/user_basket.html.twig', [
                    'message' => $message,
                    'basketPs' => $basketProducts,
                    'cities' => $cities,
                ]);
            } else {
                // Pas de panier pour cet utilisateur
                $message = $this->translator->trans('basket.no_products_yet');

                return $this->render('basket/user_basket.html.twig', [
                    'basketPs' => null, // Pas de produits à afficher
                    'message' => $message,
                ]);
            }
        } else {
            // Gère le cas où aucun utilisateur n'est connecté
            return $this->redirectToRoute('app_login');
        }
    }

    // ------------------fin show user basket --------------------------------

    // Delivery---------------------------------------------------------------------------------------
    #[Route('/delivery/calculate', name: 'app_delivery_calculate', methods: ['POST'])]
    public function calculateDelivery(
        Request $request,
        BasketProductRepository $basketProductRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $cityId = $data['city_id'] ?? null;
        $method = $data['method'] ?? 'standard';

        $basket = $user->getBasket();
        $basketProducts = $basketProductRepository->findBasketProductsByBasketId($basket);

        $totalWeight = 0.0; // kg
        $totalVolume = 0.0; // cm³

        foreach ($basketProducts as $bp) {
            $product = $bp->getProduct();
            $qty = $bp->getQuantity();

            // ⚖️ poids
            $weight = (float) ($product->getWeight() ?? 0);
            $totalWeight += $weight * $qty;

            // 📐 volume
            $w = (float) ($product->getDimensionW() ?? 0);
            $h = (float) ($product->getDimensionH() ?? 0);
            $l = (float) ($product->getDimensionL() ?? 0);

            if ($w > 0 && $h > 0 && $l > 0) {
                $totalVolume += ($w * $h * $l) * $qty;
            }
        }

        // =========================
        // RÈGLES DE TARIFICATION
        // =========================
        $price = 300; // base CVE

        // surcharge distance (exemple)
        if ($cityId) {
            $price += 200;
        }

        // poids
        $price += $totalWeight * 50; // 50 CVE / kg

        // volume (conversion cm³ → dm³)
        $price += ($totalVolume / 1000) * 30;

        // express
        if ($method === 'express') {
            $price += 300;
        }
        // dd($price);

        return $this->json([
            'success' => true,
            'price' => round($price, 2),
            'debug' => [
                'weight' => $totalWeight,
                'volume_cm3' => $totalVolume
            ]
        ]);
    }


}