<?php

namespace App\Controller;

use App\Entity\Merchant;
use App\Entity\Shop;
use App\Entity\User;
use App\Entity\City;
use App\Form\UserType;
use App\Repository\ShopRepository;
use App\Repository\UserRepository;
use App\Service\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

     if($name!==null && $adress!==null && $email!==null){
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
        $shops = $shopRepository->findAllShopsByUser($this->getUser());

        $user = $this->getUser(); // Récupère l'utilisateur connecté
        $merchant = $entityManager->getRepository(Merchant::class)->findOneBy(['user' => $user]);
        $cities = $entityManager->getRepository(City::class)->findAll();
        // dd($merchant); //
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'shops' => $shops,
            'merchant' => $merchant,
            'cities' => $cities,            
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager,
    UserRepository $userRepository, Service $service): Response
    {
        //  dd($user);    
        // $form = $this->createForm(UserType::class, $user);
        // $form->handleRequest($request);
    
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $entityManager->flush();
    
        //     return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
        // }
        
        
        $email = $request->request->get('email');
        $fName = $request->request->get('firstName');
        $lName = $request->request->get('lastName');
        $adress = $request->request->get('adress');

        if($email && $fName && $lName && $adress){


            // $email = $request->get('email');
            // $errors = $service->validateEmail($email);
    
            // return $this->render('user/edit.html.twig', [
            //     'email' => $email,
            //     'errors' => $errors,
            // ]);


            // verifier si l'email exist deja
            if($userRepository->isEmailTaken($email)){
                $error = "Cette email est deja utilisé !";
                return $this->render('user/edit.html.twig', [
                    'error' => $error,
                ]);
            }

            $user->setEmail($email);
            $user->setFirstName($fName);
            $user->setLastName($lName);
            $user->setAdress($adress);
            $entityManager->persist($user);
            $entityManager->flush();
           // dd($user->getId());
           return $this->redirectToRoute('app_user_show', ['id' => $user->getId()],Response::HTTP_SEE_OTHER);
        }
       
        return $this->render('user/edit.html.twig', [
            'user' => $user,  
            // 'form' => $form,
        ]);

        
    }

    
    

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_show', [], Response::HTTP_SEE_OTHER);
    }
}
