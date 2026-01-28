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
    use RepositoryHelperTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlertRule::class);
    }

    public function findByMonitorId(MonitorId $monitorId): array
    {
        return $this->createQueryBuilder('ar')
            ->join('ar.notificationChannel', 'nc')
            ->addSelect('nc')
            ->where('ar.monitorId.value = :monitorId')
            ->setParameter('monitorId', $monitorId->toString())
            ->orderBy('ar.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEnabledByMonitorId(MonitorId $monitorId): array
    {
        return $this->createQueryBuilder('ar')
            ->join('ar.notificationChannel', 'nc')
            ->addSelect('nc')
            ->where('ar.monitorId.value = :monitorId')
            ->andWhere('ar.isEnabled = :enabled')
            ->setParameter('monitorId', $monitorId->toString())
            ->setParameter('enabled', true)
            ->orderBy('ar.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
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
}
