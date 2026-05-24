<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Contact;
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
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use function Symfony\Component\Clock\now;
use App\Entity\Response as ResponseEntity; // attention au nom pour éviter conflit

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

                // Envoi email confirmation
                $this->emailVerifier->sendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('no-reply@tonsite.com', 'Falkon-ANK'))
                        ->to((string) $user->getEmail())
                        ->subject('Confirmez votre adresse e-mail')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );

                $this->addFlash('success', 'Vérifiez votre email avant de vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }


        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (!$id) {
            throw $this->createNotFoundException('Missing user id.');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Email vérifié avec succès.');

        return $this->redirectToRoute('app_home_page');
    }

    //-------------Verification email - reevoyer link-------------------------------------------------

    #[Route('/resend-verification', name: 'resend_verification')]
    public function resendVerification(UserRepository $userRepository, MailerInterface $mailer, Request $request): Response
    {
        $email = $request->query->get('email');

        if (!$email) {
            $this->addFlash('error', 'Email manquant.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Ce compte est déjà vérifié.');
            return $this->redirectToRoute('app_login');
        }

        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('no-reply@tonsite.com', 'Falkon-ANK'))
                ->to($user->getEmail())
                ->subject('Confirmez votre adresse e-mail')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        $this->addFlash('success', 'Email de vérification renvoyé.');

        return $this->redirectToRoute('app_login');
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

    // -------------------------CONTACTER-NOUS-------------------------------------------------------------------------------------------
    #[Route('contactnous', name: 'contact_nous', methods: ['GET', 'POST'])]
    public function contacterNous(): Response
    {
        $user = $this->getUser();

        return $this->render('contact/contact.html.twig', []);
    }



    #[Route('/contact_form_submit', name: 'contact_form_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
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
            //Cree et enregistrer sur contact
            $contact = new Contact();
            $contact->setName($name);
            $contact->setEmail($email);
            $contact->setSubject($subject);
            $contact->setMessage($message);
            $contact->setDatAct(new \DateTime());
            $entityManager->persist($contact);
            $entityManager->flush();

            // Envoi de l'e-mail
            // $emailMessage = (new Email())
            //     ->from($email)
            //     ->to('falkon674@gmail.com') // Adresse de destination
            //     ->subject($subject)
            //     ->text("Nom: $name\nEmail: $email\nMessage:\n$message")
            //     ->html("
            //         <p><strong>Nom:</strong> $name</p>
            //         <p><strong>Email:</strong> $email</p>
            //         <p><strong>Message:</strong></p>
            //         <p>$message</p>
            //     ");
            // $mailer->send($emailMessage);

            // Message de confirmation
            $this->addFlash('success', 'Sua mensagem foi enviada com sucesso. Obrigado por nos contatar!');
        } catch (\Exception $e) {
            // Gestion des erreurs d'envoi d'e-mail
            $this->addFlash('danger', 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde.');
        }

        return $this->redirectToRoute('contact_nous');
    }

    //---------------Response---------------------------------------------------------------------------------
    #[Route('/reponse/{id}', name: 'reponse', methods: ['GET', 'POST'])]
    public function show(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): Response {
        $contact = $em->getRepository(Contact::class)->find($id);
        if (!$contact) {
            throw $this->createNotFoundException(
                $translator->trans('error.contact_not_found')
            );
        }

        // Chercher si une réponse existe déjà pour ce contact
        $responseRepo = $em->getRepository(ResponseEntity::class);
        $response = $responseRepo->findOneBy(['contact' => $contact]);

        if (!$response) {
            $response = new ResponseEntity();
            $response->setContact($contact);
            $response->setDate(new \DateTime());
        }

        if ($request->isMethod('POST')) {
            $reponseText = $request->request->get('reponse');

            if (!empty($reponseText)) {
                $response->setResponse($reponseText);
                $response->setDate(new \DateTime());

                $em->persist($response);
                $em->flush();


                // Envoi mail
                $reponseText =
                    '<strong>' . $translator->trans('email.request') . ' :</strong><br>' .
                    nl2br(htmlspecialchars($contact->getMessage())) . '<br><br>' .
                    '<strong>' . $translator->trans('email.response') . ' :</strong><br>' .
                    nl2br(htmlspecialchars($request->request->get('reponse'))) .
                    '<br><br>' .
                    $translator->trans('email.signature');

                $email = (new Email())
                    ->from('no-reply@tondomaine.com')
                    ->to($contact->getEmail())
                    ->subject('Re: ' . $contact->getSubject())
                    ->html($reponseText);

                $mailer->send($email);

                $this->addFlash(
                    'success',
                    $translator->trans('flash.reply_sent_success')
                );
                // return $this->redirectToRoute('reponse', ['id' => $id]);
                return $this->redirectToRoute('admin');
            } else {
                if (!$contact) {
                    throw $this->createNotFoundException(
                        $translator->trans('error.contact_not_found')
                    );
                }
            }
        }

        return $this->render('contact/response.html.twig', [
            'contact' => $contact,
            'reponse' => $response->getResponse(),
        ]);
    }

    //------------------------- Fin de Pesponse---------------------------------------------------------


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
