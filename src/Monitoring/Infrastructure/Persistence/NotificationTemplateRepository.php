<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Alert\AlertChannel;
use App\Monitoring\Domain\Model\Alert\NotificationEventType;
use App\Monitoring\Domain\Model\Alert\NotificationTemplate;
use App\Monitoring\Domain\Repository\NotificationTemplateRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for notification templates.
 *
 * @extends ServiceEntityRepository<NotificationTemplate>
 */
class NotificationTemplateRepository extends ServiceEntityRepository implements NotificationTemplateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationTemplate::class);
    }

    public function findDefault(AlertChannel $channel, NotificationEventType $eventType): ?NotificationTemplate
    {
        return $this->createQueryBuilder('nt')
            ->where('nt.channel = :channel')
            ->andWhere('nt.eventType = :eventType')
            ->andWhere('nt.isDefault = true')
            ->setParameter('channel', $channel)
            ->setParameter('eventType', $eventType)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findById(string $id): ?NotificationTemplate
    {
        return $this->find($id);
    }

    public function findAll(?AlertChannel $channel = null, ?NotificationEventType $eventType = null): array
    {
        $qb = $this->createQueryBuilder('nt');

        if ($channel !== null) {
            $qb->andWhere('nt.channel = :channel')
                ->setParameter('channel', $channel);
        }

        if ($eventType !== null) {
            $qb->andWhere('nt.eventType = :eventType')
                ->setParameter('eventType', $eventType);
        }

        return $qb->orderBy('nt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(NotificationTemplate $template): void
    {
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
    }

    public function remove(NotificationTemplate $template): void
    {
        $this->getEntityManager()->remove($template);
        $this->getEntityManager()->flush();
    }

    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }
}
