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
    public function register(UserRepository $userRepository, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Security $security, TranslatorInterface $translator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);


        // Vérification si l'email existe déjà
        $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
        if ($existingUser) {
            $this->addFlash('success', $translator->trans('register.success'));
            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form,
            ]);
        }


        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('plainPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($password !== $confirmPassword) {
                $form->get('confirmPassword')
                    ->addError(new \Symfony\Component\Form\FormError($translator->trans('register.passwords_do_not_match')));
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
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            // Tente de valider le lien d'email
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans('verify.email_error'));
            $this->addFlash('error', $translator->trans('verify.invalid_email'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', $translator->trans('verify.success'));

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

    // -------------------------CONTACTER-NOUS-------------------------------------------------------------------------------------------
    #[Route('contactnous', name: 'contact_nous', methods: ['GET', 'POST'])]
    public function contacterNous(): Response
    {
        $user = $this->getUser();

        return $this->render('contact/contact.html.twig', []);
    }



    #[Route('/contact_form_submit', name: 'contact_form_submit', methods: ['POST'])]


    public function submit(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): Response {
        // Récupération des données du formulaire
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');

        // Validation des données
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $this->addFlash('danger', $translator->trans('contact.all_fields_required'));
            return $this->redirectToRoute('contact_page');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('danger', $translator->trans('contact.invalid_email'));
            return $this->redirectToRoute('contact_page');
        }

        try {
            $contact = new Contact();
            $contact->setName($name);
            $contact->setEmail($email);
            $contact->setSubject($subject);
            $contact->setMessage($message);
            $contact->setDatAct(new \DateTime());
            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('contact.success'));
        } catch (\Exception $e) {
            $this->addFlash('danger', $translator->trans('contact.error'));
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
            throw $this->createNotFoundException($translator->trans('contact.not_found'));
        }

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

                $email = (new Email())
                    ->from('no-reply@tondomaine.com')
                    ->to($contact->getEmail())
                    ->subject('Re: ' . $contact->getSubject())
                    ->text($reponseText);

                $mailer->send($email);

                $this->addFlash('success', $translator->trans('contact.response_saved_and_sent'));

                return $this->redirectToRoute('reponse', ['id' => $id]);
            } else {
                $this->addFlash('error', $translator->trans('contact.empty_response_error'));
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
