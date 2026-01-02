<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\Order;
use App\Form\DeliveryType;
use App\Repository\DeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/delivery')]
final class DeliveryController extends AbstractController
{
    #[Route('/delivery/{id}', name: 'app_delivery_index')]
    public function index(Order $order, DeliveryRepository $deliveryRepository): Response
    {
        $deliveries = $order->getDeliveries();

        return $this->render('delivery/index.html.twig', [
            'deliveries' => $deliveries,
            'order' => $order,
        ]);
    }

    #[Route('/{id}', name: 'app_delivery_show', methods: ['GET'])]
    public function show(Delivery $delivery): Response
    {
        return $this->render('delivery/show.html.twig', [
            'delivery' => $delivery,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_delivery_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Delivery $delivery, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DeliveryType::class, $delivery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // ✅ Passer l'ID de l'OrderCustomer pour le redirect
            return $this->redirectToRoute(
                'app_delivery_show',
                ['id' => $delivery->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('delivery/edit.html.twig', [
            'delivery' => $delivery,
            'form' => $form,
        ]);
    }


}
