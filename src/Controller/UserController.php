<?php

namespace App\Controller;

use App\Entity\Merchant;
use App\Entity\Shop;
use App\Entity\User;
use App\Entity\City;
use App\Entity\UserDeletionLog;
use App\Form\UserType;
use App\Repository\BasketRepository;
use App\Repository\MerchantRepository;
use App\Repository\ProductRepository;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\ShopRepository;
use App\Repository\UserRepository;
use App\Service\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user')]
class UserController extends AbstractController
{

    // ---------------------Merchant ------------------------
    #[Route('/active', name: 'app_merchant_active')]
    public function merchantActive(Request $request, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        $shop = new Shop();
        $name = $request->request->get('name');
        $adress = $request->request->get('adress');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        $mobile_phone = $request->request->get('mobile_phone');
        $horario = $request->request->get('horario');
        $desc = $request->request->get('desc');
        //  dd($name, $adress, $email, $phone, $mobile_phone, $desc);

        if ($name !== null && $adress !== null && $email !== null) {
            $shop->setName($name);
            $shop->setAdress($adress);
            $shop->setEmail($email);
            $shop->setPhone($phone);
            $shop->setMobilePhone($mobile_phone);
            $shop->setHorario($horario);
            $shop->setDescription($desc);
            $shop->setMerchant($user);
            $entityManager->persist($shop);
            $entityManager->flush();

            //activate  merchant user
            $user->setMerchant(1);
            $entityManager->persist($user);
            $entityManager->flush();

            // $shops = $shopRepository->findAllShopsByUser($this->getUser());
            return $this->render('user/show.html.twig', [
                'user' => $this->getUser(),
                'message' => "Comta activado com sucesso !",
                // 'shops' => $shops,
            ]);
        }
    }


    #[Route('/', name: 'app_user_show')]
    public function show(ShopRepository $shopRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $shops = $shopRepository->findAllShopsByUser($this->getUser());
        $merchant = $entityManager->getRepository(Merchant::class)->findOneBy(['user' => $user]);
        $cities = $entityManager->getRepository(City::class)->findAll();

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'shops' => $shops,
            'merchant' => $merchant,
            'cities' => $cities,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        Service $service,
        TranslatorInterface $translator
    ): Response {
        //  dd($user);    
        // $form = $this->createForm(UserType::class, $user);
        // $form->handleRequest($request);

        // if ($form->isSubmitted() && $form->isValid()) {
        //     $entityManager->flush();

        //     return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        // }

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $userEmail = $user->getEmail();
        $email = $request->request->get('email');
        $fName = $request->request->get('firstName');
        $lName = $request->request->get('lastName');
        $adress = $request->request->get('adress');

        if ($email || $fName || $lName || $adress) {

            // $email = $request->get('email');
            // $errors = $service->validateEmail($email);

            // return $this->render('user/edit.html.twig', [
            //     'email' => $email,
            //     'errors' => $errors,
            // ]);


            // verifier si l'email exist deja
            if ($userRepository->isEmailTaken($email) && ($userEmail != $email)) {
                $error = "Cette email est deja utilisé !";
                return $this->render('user/edit.html.twig', [
                    'error' => $error,
                ]);
            }

            // Si le champ n'est pas vide, on met à jour la valeur, sinon on garde l'existant
            if (!empty($email)) {
                $user->setEmail($email);
            }

            if (!empty($fName)) {
                $user->setFirstName($fName);
            }

            if (!empty($lName)) {
                $user->setLastName($lName);
            }

            if (!empty($adress)) {
                $user->setAdress($adress);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('account.update_success'));
            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            // 'form' => $form,
        ]);


    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('app_user_confirm_delete', ['id' => $user->getId()]);

        }

        return $this->redirectToRoute('app_user_show', [], Response::HTTP_SEE_OTHER);
    }

    //--Confirmation de Merchant suppression---------------
    #[Route('/{id}/confirm-delete', name: 'app_user_confirm_delete', methods: ['GET'])]
    public function confirmDelete(Request $request, User $user): Response
    {
        // Afficher une page de confirmation
        return $this->render('user/confirm_delete.html.twig', [
            'user' => $user,
            'isMerchant' => in_array('ROLE_MERCHANT', $user->getRoles()),
        ]);
    }
    #[Route('/{id}/delete', name: 'app_user_deleteConf', methods: ['POST'])]
    public function deleteConf(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        MerchantRepository $merchantRepository,
        ShopRepository $shopRepository,
        ProductRepository $productRepository,
        BasketRepository $basketRepository,
        ResetPasswordRequestRepository $resetPasswordRequestRepository,
        TokenStorageInterface $tokenStorage,
        SessionInterface $session,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator, // 👈 ajout du traducteur
    ): Response {
        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $request->get('_token'))) {
            return $this->redirectToRoute('app_user_show');
        }

        $password = $request->request->get('password');

        if (!$passwordHasher->isPasswordValid($user, $password)) {
           $this->addFlash('error', $translator->trans('user.invalid_password'));
           return $this->redirectToRoute('app_user_delete', ['id' => $user->getId()]);
        }

        $reason = $request->request->get('reason');

        if ($reason) {
            // Créer un log
            $log = new UserDeletionLog();
            $log->setReason($reason);
            $entityManager->persist($log);
        }


        // $replacementUserId = 1; // l’ID du super admin
        // $replacementUser = $entityManager->getRepository(User::class)->find($replacementUserId);

        // if (!$replacementUser) {
        //     throw $this->createNotFoundException('Utilisateur de remplacement introuvable.');
        // }

        // Dissocier les marchands liés à l'utilisateur
        $merchants = $merchantRepository->findBy(['user' => $user]);
        foreach ($merchants as $merchant) {
            $merchant->setUser(null);
        }

        // Dissocier les shops liés à l'utilisateur
        $shops = $shopRepository->findBy(['user' => $user]);
        foreach ($shops as $shop) {
            $shop->setActive(false);
            $shop->setMerchant(null);
            $shopProducts = $productRepository->findBy(['shop' => $shop]);
            foreach($shopProducts as $product){
                $product->setActive(false);
            }
        }

        // Dissocier le panier lié à l'utilisateur
        $basket = $basketRepository->findOneBy(['user' => $user]);
        //$adminBasket = $basketRepository->findOneBy(['user' => $replacementUser]);
        if ($basket) {
            // Vider les produits liés
            foreach ($basket->getBasketProducts() as $basketProduct) {
                $basket->removeBasketProduct($basketProduct);
            }
            foreach ($basket->getOrderC() as $order) {
                $order->setBasket(null);
            }
            $entityManager->flush();

            // Dissocier le panier de l'utilisateur (ou supprimer le panier si tu préfères)
            // $basket->setUser(null);
            // $entityManager->flush();

            // Optionnel : supprimer complètement le panier
            $entityManager->remove($basket);
            $entityManager->flush();
        }

        // Dissocier ou remove les reset password liés à l'utilisateur
        $resets = $resetPasswordRequestRepository->findBy(['user' => $user]);
        foreach ($resets as $reset) {
            $entityManager->remove($reset);
        }

        // Supprimer l'utilisateur
        $entityManager->remove($user);

        // Appliquer toutes les modifications en une seule fois
        $entityManager->flush();

        $this->addFlash('success', $translator->trans('user.account_deleted'));

        // Déconnexion : vider le token et la session
        $tokenStorage->setToken(null);
        $session->invalidate();
        return $this->render('user/goodbye.html.twig');
    }

}
