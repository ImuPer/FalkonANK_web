<?php

namespace App\Twig;

use App\Repository\AdsRepository;
use App\Repository\ShopRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use App\Repository\CityRepository;

class GlobalVariables extends AbstractExtension implements GlobalsInterface
{
    private $cityRepository;
    private $adsRepository;
    private $shopRepository;

    public function __construct(CityRepository $cityRepository, AdsRepository $adsRepository, ShopRepository $shopRepository)
    {
        $this->cityRepository = $cityRepository;
        $this->adsRepository = $adsRepository;
        $this->shopRepository = $shopRepository;
    }

    public function getGlobals(): array
    {
        return [
            'cities' => $this->cityRepository->findBy([], null, 50),
            'adss' => $this->adsRepository->findBy([], null, 50),
            'shops_lojas' => $this->shopRepository->findBy([], null, 50),
        ];
    }

}
