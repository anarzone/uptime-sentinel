<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Alert\EscalationPolicy;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for escalation policies.
 *
 * @extends ServiceEntityRepository<EscalationPolicy>
 */
class EscalationPolicyRepository extends ServiceEntityRepository implements EscalationPolicyRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EscalationPolicy::class);
    }

    public function findApplicableForMonitor(MonitorId $monitorId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT ep.uuid FROM escalation_policies ep WHERE ep.monitor_id_uuid = :monitorId AND ep.is_enabled = 1 ORDER BY ep.level ASC';
        $result = $conn->executeQuery($sql, ['monitorId' => $monitorId->toString()]);
        $ids = $result->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('ep')
            ->where('ep.id.value IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('ep.level', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(?MonitorId $monitorId = null): array
    {
        if ($monitorId === null) {
            return parent::findAll();
        }

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT ep.uuid FROM escalation_policies ep WHERE ep.monitor_id_uuid = :monitorId ORDER BY ep.level ASC';
        $result = $conn->executeQuery($sql, ['monitorId' => $monitorId->toString()]);
        $ids = $result->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('ep')
            ->where('ep.id.value IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('ep.level', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(EscalationPolicy $policy): void
    {
        $this->getEntityManager()->persist($policy);
        $this->getEntityManager()->flush();
    }

    public function remove(EscalationPolicy $policy): void
    {
        $this->getEntityManager()->remove($policy);
        $this->getEntityManager()->flush();
    }

    public function exists(string $id): bool
    {
        return $this->find($id) !== null;
    }
}
