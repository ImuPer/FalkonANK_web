<?php

namespace App\Twig;

use App\Repository\AdsRepository;
use App\Repository\ShopRepository;
use App\Repository\CityRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Symfony\Contracts\Cache\CacheInterface;

class GlobalVariables extends AbstractExtension implements GlobalsInterface
{
    private $cityRepository;
    private $adsRepository;
    private $shopRepository;
    private $cache;

    public function __construct(
        CityRepository $cityRepository,
        AdsRepository $adsRepository,
        ShopRepository $shopRepository,
        CacheInterface $cache
    ) {
        $this->cityRepository = $cityRepository;
        $this->adsRepository = $adsRepository;
        $this->shopRepository = $shopRepository;
        $this->cache = $cache;
    }

    public function getGlobals(): array
    {
        $cities = $this->cache->get('globals.cities', function() {
            return $this->cityRepository->findBy([], null, 50);
        });

        $adss = $this->cache->get('globals.adss', function() {
            return $this->adsRepository->findBy([], null, 50);
        });

        $shops = $this->cache->get('globals.shops_lojas', function() {
            return $this->shopRepository->findBy([], null, 50);
        });

        return [
            'cities' => $cities,
            'adss' => $adss,
            'shops_lojas' => $shops,
        ];
    }
}
