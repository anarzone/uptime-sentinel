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
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT ar.uuid FROM alert_rules ar WHERE ar.monitor_id_uuid = :monitorId';
        $result = $conn->executeQuery($sql, ['monitorId' => $monitorId->toString()]);
        $ids = $result->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('ar')
            ->where('ar.id.value IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function findEnabledByMonitorId(MonitorId $monitorId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT ar.uuid FROM alert_rules ar WHERE ar.monitor_id_uuid = :monitorId AND ar.is_enabled = 1';
        $result = $conn->executeQuery($sql, ['monitorId' => $monitorId->toString()]);
        $ids = $result->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('ar')
            ->where('ar.id.value IN (:ids)')
            ->setParameter('ids', $ids)
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

    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }
}
