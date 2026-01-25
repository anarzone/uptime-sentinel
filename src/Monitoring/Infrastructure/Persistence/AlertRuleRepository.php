<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AlertRuleRepository extends ServiceEntityRepository implements AlertRuleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlertRule::class);
    }

    public function findByMonitorId(MonitorId $monitorId): array
    {
        return $this->createQueryBuilder('ar')
            ->where('ar.monitorId.value = :monitorId')
            ->setParameter('monitorId', $monitorId->value)
            ->getQuery()
            ->execute();
    }

    public function findEnabledByMonitorId(MonitorId $monitorId): array
    {
        return $this->createQueryBuilder('ar')
            ->where('ar.monitorId.value = :monitorId')
            ->andWhere('ar.isEnabled = :enabled')
            ->setParameter('monitorId', $monitorId->value)
            ->setParameter('enabled', true)
            ->getQuery()
            ->execute();
    }

    public function save(AlertRule $alertRule): void
    {
        $this->getEntityManager()->persist($alertRule);
        $this->getEntityManager()->flush();
    }

    public function remove(AlertRule $alertRule): void
    {
        $this->getEntityManager()->remove($alertRule);
        $this->getEntityManager()->flush();
    }

    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }
}
