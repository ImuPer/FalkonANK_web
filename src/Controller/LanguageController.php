<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LanguageController extends AbstractController
{
    #[Route('/lang/{_locale}', name: 'lang_switch')]
    public function switchLang(Request $request, $_locale): RedirectResponse
    {
        $request->getSession()->set('_locale', $_locale);

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_home_page'));
    }
}
