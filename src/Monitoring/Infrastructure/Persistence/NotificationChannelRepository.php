<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Model\Notification\NotificationChannelId;
use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface;
use App\Monitoring\Domain\ValueObject\OwnerId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationChannel>
 */
final class NotificationChannelRepository extends ServiceEntityRepository implements NotificationChannelRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationChannel::class);
    }

    public function findNotificationChannel(NotificationChannelId $id): ?NotificationChannel
    {
        return $this->find($id->toString());
    }

    public function findByTarget(string $target, ?OwnerId $ownerId = null): ?NotificationChannel
    {
        $criteria = ['dsn' => $target];
        if ($ownerId !== null) {
            $criteria['ownerId'] = $ownerId->value;
        }

        return $this->findOneBy($criteria);
    }

    public function findByTypeAndTarget(NotificationChannelType $type, string $target, ?OwnerId $ownerId = null): ?NotificationChannel
    {
        $criteria = ['type' => $type, 'dsn' => $target];
        if ($ownerId !== null) {
            $criteria['ownerId'] = $ownerId->value;
        }

        return $this->findOneBy($criteria);
    }

    public function save(NotificationChannel $channel): void
    {
        $this->getEntityManager()->persist($channel);
        $this->getEntityManager()->flush();
    }

    public function remove(NotificationChannel $channel): void
    {
        $this->getEntityManager()->remove($channel);
        $this->getEntityManager()->flush();
    }

    public function findAll(): array
    {
        return parent::findAll();
    }
}
