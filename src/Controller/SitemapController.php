<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function sitemap(): Response
    {
        $urls = [
            [
                'loc' => 'https://falkon.click/',
                'lastmod' => (new \DateTime())->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => 'https://falkon.click/contact',
                'lastmod' => '2025-06-01',
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ],
        ];

        $response = $this->render('sitemap/sitemap.xml.twig', ['urls' => $urls]);
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }

}
