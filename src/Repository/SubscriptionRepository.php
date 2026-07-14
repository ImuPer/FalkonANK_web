<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function hasActiveSubscription(User $user): bool
    {
        return (bool) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.status = :status')
            ->andWhere('s.endAt > :today')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->setParameter('today', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByStripeSubscriptionId(string $subscriptionId): ?Subscription
    {
        return $this->findOneBy([
            'stripeSubscriptionId' => $subscriptionId,
        ]);
    }
}