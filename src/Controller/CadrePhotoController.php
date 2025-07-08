<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CadrePhotoController extends AbstractController
{
    #[Route('/cadrephoto', name: 'app_cadre_photo')]
    public function index(): Response
    {
        return $this->render('cadre_photo/index.html.twig', [
            'controller_name' => 'Os Nossos Quadros Digitais',
        ]);
    }
}
