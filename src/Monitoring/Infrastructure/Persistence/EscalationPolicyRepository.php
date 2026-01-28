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
    use RepositoryHelperTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EscalationPolicy::class);
    }

    public function findApplicableForMonitor(MonitorId $monitorId): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.notificationChannel', 'nc')
            ->addSelect('nc')
            ->where('ep.monitorId.value = :monitorId')
            ->andWhere('ep.isEnabled = :enabled')
            ->setParameter('monitorId', $monitorId->toString())
            ->setParameter('enabled', true)
            ->orderBy('ep.level', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(?MonitorId $monitorId = null): array
    {
        if ($monitorId === null) {
            return parent::findAll();
        }

        return $this->createQueryBuilder('ep')
            ->join('ep.notificationChannel', 'nc')
            ->addSelect('nc')
            ->where('ep.monitorId.value = :monitorId')
            ->setParameter('monitorId', $monitorId->toString())
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
}
