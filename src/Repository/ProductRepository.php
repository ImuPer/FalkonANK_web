<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    // src/Repository/ProductRepository.php

    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }

    public function findByCategoryName(string $categoryName): array
{
    return $this->createQueryBuilder('p')
        ->join('p.category', 'c')
        ->where('c.name = :categoryName')
        ->setParameter('categoryName', $categoryName)
        ->getQuery()
        ->getResult();
}

}
