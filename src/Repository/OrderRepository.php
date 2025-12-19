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
}
