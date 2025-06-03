<?php

namespace App\Controller;

use App\Entity\Merchant;
use App\Form\MerchantType;
use App\Repository\MerchantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

//#[Route('/merchant')]
final class MerchantController extends AbstractController
{
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
