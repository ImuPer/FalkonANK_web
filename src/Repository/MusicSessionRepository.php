<?php

namespace App\Repository;

use App\Entity\MusicSession;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MusicSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MusicSession::class);
    }

    /**
     * Retourne la session active d'un utilisateur.
     */
    public function findActiveByUser(User $user): ?MusicSession
    {
        return $this->createQueryBuilder('ms')
            ->andWhere('ms.user = :user')
            ->andWhere('ms.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('ms.lastActivity', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne une session par son token.
     */
    public function findByToken(string $token): ?MusicSession
    {
        return $this->createQueryBuilder('ms')
            ->andWhere('ms.token = :token')
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Désactive toutes les sessions actives d'un utilisateur.
     */
    public function deactivateAll(User $user): void
    {
        $this->createQueryBuilder('ms')
            ->update()
            ->set('ms.isActive', ':inactive')
            ->where('ms.user = :user')
            ->andWhere('ms.isActive = true')
            ->setParameter('inactive', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Retourne toutes les sessions d'un utilisateur.
     */
    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('ms')
            ->andWhere('ms.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ms.lastActivity', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime les anciennes sessions inactives.
     * (à lancer éventuellement avec une commande CRON)
     */
    public function deleteInactiveOlderThan(\DateTimeImmutable $date): int
    {
        return $this->createQueryBuilder('ms')
            ->delete()
            ->where('ms.isActive = false')
            ->andWhere('ms.lastActivity < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    public function findActiveByToken(string $token): ?MusicSession
    {
        return $this->createQueryBuilder('ms')
            ->andWhere('ms.token = :token')
            ->andWhere('ms.isActive = true')
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}