<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Merchant;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(UserRepository $userRepository, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);


        // Vérification si l'email existe déjà
        $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
        if ($existingUser) {
            $this->addFlash('error', 'Este e-mail já está em utilização.');
            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form,
            ]);
        }


        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('plainPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($password !== $confirmPassword) {
                $form->get('confirmPassword')
                    ->addError(new \Symfony\Component\Form\FormError('As senhas não correspondem.'));
            } else {
                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $password
                    )
                );

                $user->setMerchant(0);
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('app_login');
            }
        }


        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());
            $this->addFlash('error', 'Este e-mail é ivalido.');

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Seu endereço de e-mail foi verificado.');

        return $this->redirectToRoute('app_home_page');
    }



    // ---------------------------------MERCHANT--------------------------------------------------------------------------------------------------------------

    #[Route('/dashboard', name: 'user_dashboard')]
    public function showDashboard(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); // Récupère l'utilisateur connecté
        $merchant = $entityManager->getRepository(Merchant::class)->findOneBy(['user' => $user]);

        // Si le marchand n'est pas encore défini
        if (!$merchant) {
            return $this->render('user/show.html.twig', [
                'merchant' => null,  // Pas de marchand, donc envoyer null
                'user' => $user,
            ]);
        }

        // Sinon, on affiche les informations du marchand
        return $this->render('user/show.html.twig', [
            'merchant' => $merchant,
            'user' => $user,
        ]);
    }

    #[Route('registermerchand', name: 'merchant_register', methods: ['POST'])]
    public function registerMerchant(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Vérifier si l'utilisateur a déjà une demande de marchand
        $existingMerchant = $entityManager->getRepository(Merchant::class)->findOneBy(['user' => $user]);

        if ($existingMerchant) {
            $this->addFlash('error', 'Você já tem uma solicitação de loja registrada.');
            return $this->redirectToRoute('user_dashboard');
        }

        // Récupérer les données du formulaire
        $cityId = $request->request->get('city_id');
        $city = $entityManager->getRepository(City::class)->find($cityId);
        $shopName = $request->request->get('shop_name');
        $shopAddress = $request->request->get('shop_address');
        $shopDescription = $request->request->get('shop_description');
        $shopLicense = $request->files->get('shop_license');

        // Données bancaires
        $bankHolder = $request->request->get('bank_holder');
        $bankName = $request->request->get('bank_name');
        $bankIban = $request->request->get('bank_iban');
        $bankSwift = $request->request->get('bank_swift');

        // Validation des champs obligatoires
        if (empty($shopName) || empty($shopAddress) || !$shopLicense) {
            $this->addFlash('error', 'Todos os campos obrigatórios devem ser preenchidos.');
            return $this->redirectToRoute('user_basket');
        }

        // Traitement du fichier
        if ($shopLicense) {
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            $fileExtension = $shopLicense->guessExtension() ?: $shopLicense->getClientOriginalExtension();

            if (!in_array($fileExtension, $allowedExtensions)) {
                $this->addFlash('error', 'O ficheiro deve estar no formato PDF, JPG, JPEG ou PNG.');
                return $this->redirectToRoute('app_user_show');
            }

            $fileName = uniqid() . '.' . $fileExtension;
            $uploadDir = $this->getParameter('uploads_directory');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $shopLicense->move($uploadDir, $fileName);
        }

        // Création de l'entité Merchant
        $merchant = new Merchant();
        $merchant->setName($shopName);
        $merchant->setAddress($shopAddress);
        $merchant->setDescription($shopDescription);
        $merchant->setLicenseFile('uploads/' . $fileName);
        $merchant->setUser($user);
        $merchant->setCity($city);

        // Ajout des données bancaires
        $merchant->setBankHolder($bankHolder);
        $merchant->setBankName($bankName);
        $merchant->setIban($bankIban);
        $merchant->setSwift($bankSwift);

        $entityManager->persist($merchant);
        $entityManager->flush();

        $this->addFlash('success', 'Sua solicitação de criação de loja foi registrada com sucesso.');
        return $this->redirectToRoute('user_dashboard');
    }


    // -------------------------CONTACTER-NOUS-------------------------------------------------------------------------------------------
    #[Route('contactnous', name: 'contact_nous', methods: ['GET', 'POST'])]
    public function contacterNous(): Response
    {
        $user = $this->getUser();

        return $this->render('contact/contact.html.twig', []);
    }



    #[Route('/contact_form_submit', name: 'contact_form_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer): Response
    {
        // Récupération des données du formulaire
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');

        // Validation des données
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $this->addFlash('danger', 'Tous les champs sont obligatoires.');
            return $this->redirectToRoute('contact_page'); // Redirige vers la page de contact
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('danger', 'L\'adresse e-mail n\'est pas valide.');
            return $this->redirectToRoute('contact_page');
        }

        try {
            // Envoi de l'e-mail
            $emailMessage = (new Email())
                ->from($email)
                ->to('falkon674@gmail.com') // Adresse de destination
                ->subject($subject)
                ->text("Nom: $name\nEmail: $email\nMessage:\n$message")
                ->html("
                    <p><strong>Nom:</strong> $name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Message:</strong></p>
                    <p>$message</p>
                ");

            $mailer->send($emailMessage);

            // Message de confirmation
            $this->addFlash('success', 'Sua mensagem foi enviada com sucesso. Obrigado por nos contatar!');
        } catch (\Exception $e) {
            // Gestion des erreurs d'envoi d'e-mail
            $this->addFlash('danger', 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde.');
        }

        return $this->redirectToRoute('contact_nous');
    }


    //---GOTO Conditions Générales d'Utilisation-------------------------------------------
    #[Route('/conditions-generales', name: 'agree_terms')]
    public function agreeTerms(): Response
    {
        return $this->render('registration/agreeTerms.html.twig');
    }

     //---GOTO politique-de-confidentialite-------------------------------------------
     #[Route('/politique-de-confidentialite', name: 'privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('registration/privacy_policy.html.twig');
    }

}
