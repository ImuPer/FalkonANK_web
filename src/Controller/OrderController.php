<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\BasketProductRepository;
use App\Repository\BasketRepository;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_user_orders', methods: ['GET'])]
    public function index(BasketRepository $basketRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $timezone = date_default_timezone_get();
        $basket = $basketRepository->findOneBy(['user' => $user]); //recuperer le basket user
        if ($basket) {
            $criteria = Criteria::create()
                ->orderBy(['order_date' => Criteria::DESC]);

            $ordersUser = $basket->getOrders()->matching($criteria);

            return $this->render('order/index.html.twig', [
                'orders' => $ordersUser,
                'timezone_variable' => $timezone, // Passer le fuseau horaire à Twig
            ]);
        } else {
            // Pas de panier pour cet utilisateur
            $message = "Você ainda não fez compras, adicione produtos ao carrinho e realiza bu primeru kompra !";

            return $this->render('order/index.html.twig', [
                'orders' => null, // Pas de produits à afficher
                'timezone_variable' => $timezone, // Passer le fuseau horaire à Twig
                'message' => $message,
            ]);
        }


    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order, BasketProductRepository $basketProductRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $basketProducts = $basketProductRepository->findBy(['orderC' => $order]);
        // dd($basketProducts);
        return $this->render('order/show.html.twig', [
            'order' => $order,
            'basketPs' => $basketProducts,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/comment', name: 'app_order_comment', methods: ['POST'])]
    public function comment(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        $note = $request->request->get('customer_note');

        if ($note) {
            $order->setCustomerNote($note);
            $em->flush();

            $this->addFlash('success', 'Comentário adicionado com sucesso!');
        }

        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }

    //------------------ reçu order - client----------------------------------------------------
    #[Route('/order/print/{ref}', name: 'order_print')]
    public function print(OrderRepository $orderRepository, string $ref): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $order = $orderRepository->findOneBy(['ref' => $ref]);
        if (!$order) {
            $this->addFlash('error', '❌ Commande introuvable ou référence invalide.');
            return $this->redirectToRoute('app_user_orders'); // ou app_login / orders_list
        }
        if ($order->getBasket()->getUser() !== $this->getUser()) {
            $this->addFlash('error', '⛔ Accès refusé à cette commande.');
            return $this->redirectToRoute('app_home_page');
        }

        return $this->render('order/print.html.twig', [
            'order' => $order
        ]);
    }

}
