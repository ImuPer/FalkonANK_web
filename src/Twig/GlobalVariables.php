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
            'cities' => $this->cityRepository->findAll(),
            'adss' => $this->adsRepository->findAll(),
            'shops_lojas' =>$this->shopRepository->findAll(),
        ];
    }
}
