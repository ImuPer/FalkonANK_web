<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CadrePhotoController extends AbstractController
{
    #[Route('/cadrephoto', name: 'app_cadre_photo')]
    public function index(TranslatorInterface $translator): Response
    {
        return $this->render('cadre_photo/index.html.twig', [
            'controller_name' => $translator->trans('cadre_photo.title')
        ]);
    }
}
