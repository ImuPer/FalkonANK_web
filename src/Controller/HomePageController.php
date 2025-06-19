<?php

namespace App\Controller;

use App\Entity\Shop;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\ProductRepository;
use App\Repository\ShopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

class HomePageController extends AbstractController
{

        #[Route('shops', name: 'app_shop_all')]
    public function shops(): Response
    {
        return $this->render('home_page/shops.html.twig');
    }

    #[Route('/', name: 'app_home_page')]
    public function index(ProductRepository $productRepository, CityRepository $cityRepository, ShopRepository $shopRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('home_page/index.html.twig', [
            'products' => $productRepository->findAll(),
            'cities' => $cityRepository->findAll(),
            'shops' => $shopRepository->findAll(),
            'categories' => $categoryRepository->findAll()
        ]);
    }

    #[Route('/search', name: 'product_search')]
    public function search(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $query = $request->query->get('search');

        $products = [];

        if ($query) {
            $products = $productRepository->findByName($query);
        }

        return $this->render('home_page/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll(),
            'searchQuery' => $query
        ]);
    }

    #[Route('/shop/{id}', name: 'shop_products')]
    public function products($id, ShopRepository $shopRepository, CityRepository $cityRepository, CategoryRepository $categoryRepository): Response
    {
        $shop = $shopRepository->find($id);

        if (!$shop) {
            throw $this->createNotFoundException("Shop not found");
        }

        return $this->render('home_page/index.html.twig', [
            'shop' => $shop,
            'products' => $shop->getProducts(),
            'cities' => $cityRepository->findAll(),
            'categories' => $categoryRepository->findAll()
        ]);
    }

    #[Route('about', name: 'app_about_page')]
    public function aboutPage(): Response
    {
        return $this->render('home_page/about.html.twig', [
            // 'products' => $productRepository->findAll(),
        ]);

    }


    #[Route('/category/{name}', name: 'products_by_category')]
    public function productsByCategory(string $name, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $products = $productRepository->findByCategoryName($name);

        if (empty($products)) {
            $this->addFlash('warning', "Nenhum produto encontrado para a categoria '$name'.");
        }

        return $this->render('home_page/index.html.twig', [
            'products' => $products,
            'categoryName' => $name,
            'categories' => $categoryRepository->findAll()
        ]);
    }

   #[Route('/{id}', name: 'app_shop_loja', requirements: ['id' => '\d+'])]
public function shopShow(?Shop $shop): Response
{
        if (!$shop) {
            throw $this->createNotFoundException('Loja não encontrada.');
        }
        return $this->render('home_page/shops.html.twig', [
            'shop_loja' => $shop,
        ]);
    }
    


    // -------------------------CONTACTER-NOUS-------------------------------------------------------------------------------------------
    // #[Route('contactnous', name: 'contact_nous', methods: ['GET', 'POST'])]
    // public function contacterNous(): Response
    // { 
    //     $user = $this->getUser();

    //     return $this->render('contact/contact.html.twig', []);
    // }



    // #[Route('/contact_form_submit', name: 'contact_form_submit', methods: ['POST'])]
    // public function submit(Request $request, MailerInterface $mailer): Response
    // {
    //     // Récupération des données du formulaire
    //     $name = $request->request->get('name');
    //     $email = $request->request->get('email');
    //     $subject = $request->request->get('subject');
    //     $message = $request->request->get('message');

    //     // Validation des données
    //     if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    //         $this->addFlash('danger', 'Tous les champs sont obligatoires.');
    //         return $this->redirectToRoute('contact_page'); // Redirige vers la page de contact
    //     }

    //     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //         $this->addFlash('danger', 'L\'adresse e-mail n\'est pas valide.');
    //         return $this->redirectToRoute('contact_page');
    //     }

    //     try {
    //         // Envoi de l'e-mail
    //         $emailMessage = (new Email())
    //             ->from($email)
    //             ->to('toimuhotepu@hotmail.com') // Adresse de destination
    //             ->subject($subject)
    //             ->text("Nom: $name\nEmail: $email\nMessage:\n$message")
    //             ->html("
    //                 <p><strong>Nom:</strong> $name</p>
    //                 <p><strong>Email:</strong> $email</p>
    //                 <p><strong>Message:</strong></p>
    //                 <p>$message</p>
    //             ");

    //         $mailer->send($emailMessage);

    //         // Message de confirmation
    //         $this->addFlash('success', 'Sua mensagem foi enviada com sucesso. Obrigado por nos contatar!');
    //     } catch (\Exception $e) {
    //         // Gestion des erreurs d'envoi d'e-mail
    //         $this->addFlash('danger', 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde.');
    //     }

    //     return $this->redirectToRoute('contact_nous');
    // }


    

    //-------------APP ImuhotepuVideos---------------------------------------------------
    #[Route('/imuhotepu', name: 'app_presentation')]
     public function showAppPage(): Response
{
    $captures = [
        'screen1.png',
        'screen2.png',
        'screen3.png',
        // etc.
    ];

    return $this->render('home_page/app_presentation.html.twig', [
        'captures' => $captures,
    ]);
}




}
