<?php

namespace App\Repository;

use App\Entity\BasketProduct;
use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BasketProduct>
 */
class BasketProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BasketProduct::class);
    }

    public function showBasketAndProduct($basket, $product)
    {
        return $this->findOneBy([
            'basket' => $basket,
            'product' => $product,
            'payment' => false,
        ]);
    }

    public function findBasketProductsByBasketId($basketId)
    {
        return $this->createQueryBuilder('bp')
            ->join('bp.basket', 'b')
            ->where('b.id = :basketId')
            ->andWhere('bp.payment = :payment')
            ->setParameter('basketId', $basketId)
            ->setParameter('payment', false)
            ->getQuery()
            ->getResult();
    }

    // basketProduct for order 
    public function findBasketProductsByOrderId($orderId)
    {
        return $this->createQueryBuilder('bp')
            ->join('bp.orderC', 'o')
            ->where('o.id = :orderId')
            ->andWhere('bp.payment = :payment')
            ->setParameter('orderId', $orderId)
            ->setParameter('payment', true)
            ->getQuery()
            ->getResult();
    }

    // quantity of products in user basket
    public function getTotalQuantityForBasketWherePaymentFalse($basketId): int
    {
        $qb = $this->createQueryBuilder('bp')
            ->select('SUM(bp.quantity)')
            ->where('bp.basket = :basketId')
            ->andWhere('bp.payment = :payment')
            ->setParameter('basketId', $basketId)
            ->setParameter('payment', false);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }


    //------CONTABILITE-------------------------------------------------------------------
    // Mois especifique
    public function getMonthlyRevenueByShopAndMonth(Shop $shop, \DateTimeInterface $month): float
    {
        $start = (new \DateTime($month->format('Y-m-01')))->setTime(0, 0, 0);
        $end = (new \DateTime($month->format('Y-m-t')))->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('bp')
            ->select('SUM(p.price * bp.quantity) as revenue')
            ->join('bp.product', 'p')
            ->join('p.shop', 's')
            ->join('bp.orderC', 'o')
            ->where('s = :shop')
            ->andWhere('bp.payment = true')
            ->andWhere('bp.date_pay BETWEEN :start AND :end')
            ->andWhere('o.order_status = :status')
            ->setParameter('shop', $shop)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', 'Entregue e finalizado');

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    // Liste de mois
    public function getRevenueByShopAndDateRange(Shop $shop, \DateTimeInterface $start, \DateTimeInterface $end): ?float
{
    return $this->createQueryBuilder('bp')
        ->select('SUM(p.price * bp.quantity)')
        ->join('bp.product', 'p')
        ->join('p.shop', 's')
        ->join('bp.orderC', 'o')
        ->andWhere('s = :shop')
        ->andWhere('bp.payment = true')
        ->andWhere('bp.date_pay BETWEEN :start AND :end')
        ->andWhere('o.order_status = :status')
        ->setParameter('shop', $shop)
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->setParameter('status', 'completed')
        ->getQuery()
        ->getSingleScalarResult();
}





}
