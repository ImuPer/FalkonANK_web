<?php

namespace App\Repository;

use App\Entity\Merchant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Merchant>
 */
class MerchantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Merchant::class);
    }

    // Vous pouvez ajouter des méthodes personnalisées ici si nécese.
    // Par exemple, une méthode pour obtenir un marchand par son nom
    public function findByShopName(string $shopName)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.name = :name')
            ->setParameter('name', $shopName)
            ->getQuery()
            ->getResult();
    }
}
