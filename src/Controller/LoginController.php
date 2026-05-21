<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {

        // if already logged in
        if ($this->getUser()) {

            $redirect = $request->query->get('redirect');

            if ($redirect) {
                return $this->redirect($redirect);
            }

            return $this->redirectToRoute('app_home');
        }

        // get login error
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'redirect' => $request->query->get('redirect'),
        ]);
    }
}