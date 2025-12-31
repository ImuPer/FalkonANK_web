<?php

namespace App\Repository;

use App\Entity\Delivery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Delivery>
 */
class DeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delivery::class);
    }

    
    public function generateUniqueTrackingNumber(): string
{
    do {
        $trackingNumber = sprintf(
            'DLV-%s-%s',
            date('Y'),
            strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))
        );

        $exists = $this->findOneBy(['tracking_number' => $trackingNumber]);
    } while ($exists !== null);

    return $trackingNumber;
}

}
