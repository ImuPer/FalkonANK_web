<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function refOrderExists(string $refOrder): bool
    {
        // Création du QueryBuilder pour interroger la base de données
        $qb = $this->createQueryBuilder('o');
        $qb->select('count(o.id)')
           ->where('o.ref = :refOrder')
           ->setParameter('refOrder', $refOrder);

        // Exécution de la requête et récupération du résultat
        $count = $qb->getQuery()->getSingleScalarResult();

        // Si count > 0, cela signifie que le ref_order existe
        return $count > 0;
    }
}
