<?php

namespace App\Repository;

use App\Entity\AlbumPurchase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlbumPurchase>
 */
class AlbumPurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumPurchase::class);
    }

    public function hasUserBoughtAlbum(int $userId, int $albumId): bool
    {
        return (bool) $this->createQueryBuilder('ap')
            ->select('COUNT(ap.id)')
            ->andWhere('ap.user = :user')
            ->andWhere('ap.album = :album')
            ->setParameter('user', $userId)
            ->setParameter('album', $albumId)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
}
