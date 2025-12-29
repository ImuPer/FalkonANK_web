<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
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

    public function secretCodeOrderExists(string $secretCodeOrder): bool
    {
        // Création du QueryBuilder pour interroger la base de données
        $qb = $this->createQueryBuilder('o');
        $qb->select('count(o.id)')
            ->where('o.autoSecretCode = :autoSecretCodeOrder')
            ->setParameter('autoSecretCodeOrder', $secretCodeOrder);

        // Exécution de la requête et récupération du résultat
        $count = $qb->getQuery()->getSingleScalarResult();

        // Si count > 0, cela signifie que le secretCode existe
        return $count > 0;
    }

    public function generateUniqueOrderSecretCode(int $userId): string
    {
        do {
            // Génère un code aléatoire à 6 chiffres (avec des zéros si nécessaire)
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $secretCodOrder = (string) $userId . $code;
        } while ($this->secretCodeOrderExists($secretCodOrder));

        return $secretCodOrder;
    }

    /**
     * Compte le nombre d'ordres en traitement ou sans merchantSecretCode
     */
    public function countPendingOrMissingSecret(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.order_status = :status')
            ->orWhere('o.merchantSecretCode IS NULL')
            ->setParameter('status', 'Em processamento')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre d'ordres en traitement ou sans merchantSecretCode pour un merchant spécifique
     */
    public function countPendingOrMissingSecretByMerchant(User $merchantUser): int
    {
        $qb = $this->createQueryBuilder('o');

        // Join com basketProducts → product → shop → user
        $qb->select('COALESCE(COUNT(DISTINCT o.id), 0)')
            ->join('o.basketProducts', 'bp')
            ->join('bp.product', 'p')
            ->join('p.shop', 's')
            ->join('s.user', 'u')
            ->where('u = :merchantUser')
            ->andWhere(
                $qb->expr()->orX(
                    'o.order_status = :status',
                    'o.merchantSecretCode IS NULL'
                )
            )
            ->setParameter('merchantUser', $merchantUser)
            ->setParameter('status', 'Em processamento');

        return (int) $qb->getQuery()->getSingleScalarResult();

    }


    /**
     * Compte le nombre de commandes en remboursement (en cours)
     */
    public function countRefundInProgress(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.order_status = :status')
            ->andWhere('o.refund_status = :refund')
            ->setParameter('status', 'Reembolso')
            ->setParameter('refund', 'Em curso')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
       /**
     * Compte le nombre de commandes en remboursement (Em processamento), pour un userMerchant en specific
     */
    public function countOrderInProgressByMerchant(User $merchantUser): int
    {
        $qb = $this->createQueryBuilder('o');

        // Join avec basketProducts → product → shop → user
        $qb->select('COALESCE(COUNT(DISTINCT o.id), 0)')
            ->join('o.basketProducts', 'bp')
            ->join('bp.product', 'p')
            ->join('p.shop', 's')
            ->join('s.user', 'u')
            ->where('u = :merchantUser')
            ->andWhere('o.order_status = :orderStatus')
            ->setParameter('merchantUser', $merchantUser)
            ->setParameter('orderStatus', 'Em processamento');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }


     /**
     * Compte le nombre de commandes en remboursement (en cours), pour un userMerchant en specific
     */
    public function countRefundInProgressByMerchant(User $merchantUser): int
    {
        $qb = $this->createQueryBuilder('o');

        // Join avec basketProducts → product → shop → user
        $qb->select('COALESCE(COUNT(DISTINCT o.id), 0)')
            ->join('o.basketProducts', 'bp')
            ->join('bp.product', 'p')
            ->join('p.shop', 's')
            ->join('s.user', 'u')
            ->where('u = :merchantUser')
            ->andWhere('o.order_status = :orderStatus')
            ->andWhere('o.refund_status = :refundStatus')
            ->setParameter('merchantUser', $merchantUser)
            ->setParameter('orderStatus', 'Reembolso')
            ->setParameter('refundStatus', 'Em curso');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

     /**
     * Compte le nombre de commandes deja remboursé (en cours), pour un userMerchant en specific
     */
    public function countRefundFinishByMerchant(User $merchantUser): int
    {
        $qb = $this->createQueryBuilder('o');

        // Join avec basketProducts → product → shop → user
        $qb->select('COALESCE(COUNT(DISTINCT o.id), 0)')
            ->join('o.basketProducts', 'bp')
            ->join('bp.product', 'p')
            ->join('p.shop', 's')
            ->join('s.user', 'u')
            ->where('u = :merchantUser')
            ->andWhere('o.refund_status = :refundStatus')
            ->setParameter('merchantUser', $merchantUser)
            ->setParameter('refundStatus', 'Reembolsado');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

}
