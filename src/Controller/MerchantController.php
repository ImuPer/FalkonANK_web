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

//#[Route('/merchant')]
final class MerchantController extends AbstractController
{

    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/merchant/edit/{id}', name: 'merchant_edit')]
    public function edit(Request $request, Merchant $merchant, EntityManagerInterface $em): Response
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

            $this->addFlash('success', 'Dados atualizados com sucesso!');
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
        MailerInterface $mailer
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
            $this->addFlash('error', 'Você já tem uma solicitação de loja registrada.');
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

        //-----------Envoyer l’email avec le PDF en pièce jointe------------------------------------------
    

        $contractEmailContent = <<<EOD
<html>
  <body style="font-family: Arial, sans-serif; font-size: 16px; color: #333;">
    <div style="text-align: center; margin-bottom: 20px;">
      <img src="https://falkon.click/image/FalkonANK/logo-transparent-png.png" alt="FalkonANK Logo" style="max-width: 100px; height: auto;">
    </div>

    <p>Olá <strong>{$userName}</strong>,</p>

    <p>Obrigado por escolher fazer parte da nossa rede de parceiros comerciais. Estamos entusiasmados com a sua colaboração. Abaixo está o resumo do seu contrato de parceria comercial:</p>

    <p>
      <strong>Número do Contrato:</strong> CT-USER-{$userID}-{$dateNowFormatted0}<br>
      <strong>Data de Assinatura:</strong> {$dateNowFormatted}
    </p>

    <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px;">Detalhes do Contrato</h3>
    <p>
      <strong>Entre:</strong> <br>
      <strong>A Plataforma :</strong> FalkonANK Alimentason (nome legal: <em>Pereira Mascarenhas Milton Mario</em>), com sede em 60 rue François 1er, 75008 Paris, França.<br>
      <strong>E :</strong> <br>
      <strong>Comerciante : </strong> {$userName}<br>
      <strong>NIF : </strong> {$nifMerchant}<br>
      <strong>Morada do Estabelecimento : </strong> {$shopAddress}<br>
      <strong>Cidade : </strong> {$city}
      <strong>E-mail de Contacto : </strong> {$userEmail}
    </p>

    <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px;">Cláusulas do Contrato</h3>

    <p><strong>1. Objeto:</strong> Este contrato estabelece os termos da parceria para venda e entrega de produtos através da plataforma FalkonANK Alimentason.</p>
    <p><strong>2. Obrigações da Plataforma:</strong> Divulgar produtos, processar pagamentos, encaminhar pedidos ao Comerciante e fornecer suporte ao cliente.</p>
    <p><strong>3. Obrigações do Comerciante:</strong> Garantir a qualidade e disponibilidade dos produtos e entregar conforme acordado.</p>
    <p><strong>4. Preços e Pagamentos:</strong></p>
    <ul>
      <li>O Comerciante define o preço base (sem comissões).</li>
      <li>A Plataforma adiciona a comissão + taxas Stripe ao valor final.</li>
      <li>Pagamentos serão feitos entre 7 a 10 dias úteis após a entrega.</li>
      <li>O custo da transferência bancária internacional será deduzido.</li>
    </ul>
    <p><strong>5. Duração e Rescisão:</strong> Contrato por tempo indeterminado, com aviso prévio de 15 dias para rescisão.</p>
    <p><strong>6. Responsabilidade:</strong> O Comerciante é responsável pelos produtos entregues.</p>
    <p><strong>7. Proteção de Dados:</strong> Ambas as partes devem cumprir o RGPD.</p>
    <p><strong>8. Foro:</strong> Para resolução de litígios, fica eleito o foro da Comarca de Praia, Cabo Verde.</p>

    <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 5px;">Assinatura</h3>
    <p><strong>Nome do Comerciante : </strong> {$userName}</p>
    <p><strong>Data : </strong> {$dateNowFormatted}</p>
    <p><strong>Assinado em : </strong> {$city}</p>

    <p style="margin-top: 30px;">Atenciosamente,<br>
    <strong>FALKON-ANK</strong></p>
  </body>
</html>
EOD;


        $emailClient = (new Email())
            ->from(new Address('no-reply@FalkonANK.com', 'FalkonANK Alimentason'))
            ->to($userEmail, "falkon674@gmail.com")
            ->subject('📄 Seu contrato de parceria com a FalkonANK')
            ->html("Olá $userName,\n\nSegue em anexo o seu contrato de parceria com a FalkonANK.\n\nPor favor, guarde este documento.\n\nAtenciosamente,\nEquipe FalkonANK");

        //-------Contract en PDF--------------------
        $options = new Options();
        $options->set('defaultFont', 'Arial');

        set_time_limit(3000); 
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($contractEmailContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Enregistrement temporaire
        $pdfOutput = $dompdf->output();
        $tempPdfPath = sys_get_temp_dir() . '/receipt_' . uniqid() . '.pdf';
        file_put_contents($tempPdfPath, $pdfOutput);

        // Attacher le fichier PDF à l'e-mail
        $emailClient->attachFromPath($tempPdfPath, 'Contrato-FalkonANK.pdf');

        $mailer->send($emailClient);
        unlink($tempPdfPath);
        //------------------------------------Fin de Email & Pdf-----------------------------------------------

        $this->addFlash('success', 'Sua solicitação de criação de loja foi registrada com sucesso.');
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
