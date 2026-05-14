<?php

namespace App\Controller;

use App\Repository\MusicRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MusicController extends AbstractController
{
    #[Route('/product/{id}/music', name: 'app_music_by_product')]
    public function byProduct(int $id, ProductRepository $productRepository, MusicRepository $musicRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $musics = $musicRepository->findBy(['product' => $product]);

        return $this->render('music/index.html.twig', [
            'product' => $product,
            'musics' => $musics,
        ]);
    }
}