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
                return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
            }

            // Récupérer le produit du panier par son ID
            $basketProduct = $basketProductRepository->find($id);
            if (!$basketProduct) {
                return new JsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
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
        return new JsonResponse(['success' => false, 'message' => 'User not authenticated.'], 403);
    }
    //--------------Fin de delite products----------------------------------------



    // --------------------------------Edit quantity--------------------------------
    #[Route('/basket/edit/{id}/{quantity}', name: 'app_quantity_edit', methods: ['POST'])]
    public function editQuantity(int $id, int $quantity, EntityManagerInterface $entityManager, BasketProductRepository $basketProductRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not authenticated'], 403);
        }

        $basketProduct = $basketProductRepository->find($id);
        if (!$basketProduct) {
            return new JsonResponse(['success' => false, 'message' => 'Product not found'], 404);
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
            return $this->redirectToRoute('app_home_page');
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    //--------------Fin d'add products----------------------------------------



    // ------------------Show user basket --------------------------------
    #[Route('/', name: 'user_basket', methods: ['GET'])]
    public function showUserBasket(
        BasketRepository $basketRepository,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        $user = $this->getUser(); // Récupère l'utilisateur actuellement connecté

        if ($user) {
            $basket = $basketRepository->findOneBy(['user' => $user]);

            if ($basket) {
                $basketProducts = $basket->getBasketProducts();

                // Filtrer les produits non payés
                $basketProducts = $basketProducts->filter(function ($basketProduct) {
                    return !$basketProduct->isPayment();
                });

                $message = '';
                if ($basketProducts->isEmpty()) {
                    $message = $translator->trans('basket.empty_message');
                }

                $cities = $entityManager->getRepository(City::class)->findAll();

                return $this->render('basket/user_basket.html.twig', [
                    'message' => $message,
                    'basketPs' => $basketProducts,
                    'cities' => $cities,
                ]);
            } else {
                // Pas de panier pour cet utilisateur
                $message = $translator->trans('basket.no_items_message');

                return $this->render('basket/user_basket.html.twig', [
                    'basketPs' => null,
                    'message' => $message,
                ]);
            }
        } else {
            return $this->redirectToRoute('app_login');
        }
    }


    // ------------------fin show user basket --------------------------------


}