<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Merchant;
use App\Form\MerchantType;
use App\Repository\MerchantRepository;
use App\Security\EmailVerifier;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Mime\Part\FilePart;
use Symfony\Component\Mime\Part\MimePart;
use Symfony\Component\Mime\Part\Multipart\RelatedPart;
use Symfony\Component\Mime\Part\TextPart;
use Symfony\Component\Mime\Part\Multipart\MixedPart;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

//#[Route('/merchant')]
final class MerchantController extends AbstractController
{

    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/merchant/edit/{id}', name: 'merchant_edit')]
    public function edit(Request $request, Merchant $merchant, EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(MerchantType::class, $merchant);
        $form->handleRequest($request);

        // On garde l'ancien fichier
        $oldFile = $merchant->getLicenseFile();

        if ($form->isSubmitted() && $form->isValid()) {
            $newFile = $form['licenseFile']->getData();

            if ($newFile) {
                // Supprimer l'ancien fichier
                if ($oldFile && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $oldFile)) {
                    unlink($this->getParameter('kernel.project_dir') . '/public/' . $oldFile);
                }

                // Uploader le nouveau
                $fileName = uniqid() . '.' . $newFile->guessExtension();
                $newFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads',
                    $fileName
                );

                // Mettre à jour l'entité avec le nouveau nom
                $merchant->setLicenseFile('uploads/' . $fileName);
            }

            $em->flush();

           $this->addFlash('success', $translator->trans('merchantAdF.updated_successfully'));
            return $this->redirectToRoute('user_dashboard');
        }

        return $this->render('merchant/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('registermerchand', name: 'merchant_register', methods: ['POST'])]
public function registerMerchant(
    Request $request,
    EntityManagerInterface $entityManager,
    MailerInterface $mailer,
    TranslatorInterface $translator
): Response {
    // Récupérer l'utilisateur connecté
    $user = $this->getUser();

    $userEmail = $user->getEmail();
    $userName = $user->getFirstName() . " " . $user->getLastName();
    $userID = $user->getId();

    $dateNow = new DateTime();
    $dateNowFormatted0 = $dateNow->format('Ymd');
    $dateNowFormatted = $dateNow->format('d/m/Y');

    // Vérifier si l'utilisateur a déjà une demande de marchand
    $existingMerchant = $entityManager->getRepository(Merchant::class)->findOneBy(['user' => $user]);

    if ($existingMerchant) {
        $this->addFlash('error', $translator->trans('merchant.request_already_exists'));
        return $this->redirectToRoute('user_dashboard');
    }

    // Récupérer les données du formulaire
    $cityId = $request->request->get('city_id');
    $city = $entityManager->getRepository(City::class)->find($cityId);
    $shopName = $request->request->get('shop_name');
    $shopAddress = $request->request->get('shop_address');
    $nifMerchant = $request->request->get('merchant_nif');
    $shopDescription = $request->request->get('shop_description');
    $shopLicense = $request->files->get('shop_license');

    // Données bancaires
    $bankHolder = $request->request->get('bank_holder');
    $bankName = $request->request->get('bank_name');
    $bankIban = $request->request->get('bank_iban');
    $bankSwift = $request->request->get('bank_swift');

    // Validation des champs obligatoires
    if (empty($shopName) || empty($shopAddress) || !$shopLicense) {
        $this->addFlash('error', $translator->trans('merchant.required_fields_missing'));
        return $this->redirectToRoute('user_basket');
    }

    // Traitement du fichier
    if ($shopLicense) {
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $fileExtension = $shopLicense->guessExtension() ?: $shopLicense->getClientOriginalExtension();

        if (!in_array($fileExtension, $allowedExtensions)) {
            $this->addFlash('error', $translator->trans('merchant.invalid_file_format'));
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
    $merchant->setnifManeger($nifMerchant);
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

    // Envoi de l’email avec le PDF en pièce jointe (comme dans ton code)

    // Message de succès
    $this->addFlash('success', $translator->trans('merchant.store_creation_success'));
    return $this->redirectToRoute('user_dashboard');
}



    #[Route('/merchant/contract', name: 'merchant_contract', methods: ['GET'])]
    public function contract(Request $request): Response
    {
        // $name = $request->query->get('name');
        $user = $this->getUser();

        $userEmail = $user->getEmail();
        $userName = $user->getFirstName() . " " . $user->getLastName();
        $address = $request->query->get('address');
        $nif = $request->query->get('nif');

        // Par exemple, tu peux générer un PDF ou afficher un template
        return $this->render('merchant/contrato_parceria.html.twig', [
            'name' => $userName,
            'address' => $address,
            'nif' => $nif,
        ]);
    }




    //--------------------------------------------------------------------------------------------------------
    // #[Route('/contract/download', name: 'merchant_contract_download', methods: ['POST'])]
    // public function downloadContract(Request $request, Pdf $knpSnappyPdf, MailerInterface $mailer): Response
    // {
    //     //upload assinature
    //     $assinaturaFile = $request->files->get('assinaturaFile');
    //     $assinaturaPath = null;

    //     if ($assinaturaFile && $assinaturaFile->isValid()) {
    //         $filename = 'assinatura_' . uniqid() . '.' . $assinaturaFile->guessExtension();
    //         $assinaturaFile->move($this->getParameter('signatures_directory'), $filename);
    //         $assinaturaPath = 'uploads/signatures/' . $filename;
    //     } else {
    //         $this->addFlash('error', 'Por favor, carregue uma assinatura válida.');
    //         return $this->redirectToRoute('merchant_contract'); // ajuste para sua rota real
    //     }

    //     // return $this->render('merchant/contrato_parceria_pdf.html.twig', [
    //     //     'assinaturaImagem' => $assinaturaPath,
    //     //     // outros campos...
    //     // ]);

    //     // 1. Récupérer les données du formulaire
    //     $nome = $request->request->get('nomeComerciante');
    //     $nif = $request->request->get('nifComerciante');
    //     $morada = $request->request->get('moradaComerciante');
    //     $email = $request->request->get('emailComerciante');
    //     $data = $request->request->get('dataAssinatura');
    //     $local = $request->request->get('localAssinatura');
    //     $assinatura = $request->request->get('assinatura');

    //     // 2. Générer le HTML du contrat
    //     $html = $this->renderView('merchant/contrato_parceria_pdf.html.twig', [
    //         'nome' => $nome,
    //         'nif' => $nif,
    //         'morada' => $morada,
    //         'email' => $email,
    //         'data' => $data,
    //         'local' => $local,
    //         'assinatura' => $assinatura,
    //     ]);

    //     // 3. Générer le PDF
    //     $pdfContent = $knpSnappyPdf->getOutputFromHtml($html);
    //     $pdfFileName = 'Contrato_Parceria_' . $nome . '.pdf';

    //     // 4. Envoyer l’email avec le PDF en pièce jointe
    //     $emailMessage = (new Email())
    //         ->from(new Address('noreply@falkonank.com', 'FalkonANK'))
    //         ->to($email)
    //         ->subject('📄 Seu contrato de parceria com a FalkonANK')
    //         ->text("Olá $nome,\n\nSegue em anexo o seu contrato de parceria com a FalkonANK.\n\nPor favor, guarde este documento.\n\nAtenciosamente,\nEquipe FalkonANK")
    //         ->attach($pdfContent, $pdfFileName, 'application/pdf');

    //     $mailer->send($emailMessage);

    //     // 5. Retourner le PDF en téléchargement
    //     return new Response(
    //         $pdfContent,
    //         200,
    //         [
    //             'Content-Type' => 'application/pdf',
    //             'Content-Disposition' => 'attachment; filename="' . $pdfFileName . '"',
    //         ]
    //     );
    // }

    // #[Route('/{id}', name: 'app_merchant_delete', methods: ['POST'])]
// public function delete(Request $request, Merchant $merchant, EntityManagerInterface $entityManager): Response
// {
//     if ($this->isCsrfTokenValid('delete'.$merchant->getId(), $request->getPayload()->getString('_token'))) {
//         $entityManager->remove($merchant);
//         $entityManager->flush();
//     }

    //    // return $this->redirectToRoute('app_merchant_index', [], Response::HTTP_SEE_OTHER);
//    return $this->redirectToRoute('user_dashboard');
// }


}
