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

    public function findPaginated(int $page, int $limit, ?\App\Monitoring\Domain\ValueObject\OwnerId $ownerId = null): array
    {
        $qb = $this->createQueryBuilder('ar')
            ->join('ar.notificationChannel', 'nc')
            ->addSelect('nc')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            // Join monitor to potentially order by monitor name in future, but for now createdAt is fine
            ->orderBy('ar.createdAt', 'DESC');

        if ($ownerId !== null) {
            // Assuming AlertRule has an ownerId field?
            // Checking CreateAlertRuleHandler, it sets ownerId.
            // Let's check AlertRule entity to be sure about the field name.
            // Wait, CreateAlertRuleHandler uses 'requesterId' for ownership check but does it persist it?
            // AlertRule::create arguments: monitorId, notificationChannel, threshold, type.
            // Does it have ownerId? monitor has ownerId. AlertRule belongs to Monitor.
            // Policies don't have ownerId.
            // So filtering by ownerId means filtering by ar.monitor.ownerId.

            $qb->join(\App\Monitoring\Domain\Model\Monitor\Monitor::class, 'm', \Doctrine\ORM\Query\Expr\Join::WITH, 'ar.monitorId.value = m.id.value')
                ->andWhere('m.ownerId = :ownerId')
                ->setParameter('ownerId', $ownerId->value);
        }

        return $qb->getQuery()->getResult();
    }

    public function countTotal(?\App\Monitoring\Domain\ValueObject\OwnerId $ownerId = null): int
    {
        $qb = $this->createQueryBuilder('ar')
            ->select('COUNT(ar)');

        if ($ownerId !== null) {
            $qb->join(\App\Monitoring\Domain\Model\Monitor\Monitor::class, 'm', \Doctrine\ORM\Query\Expr\Join::WITH, 'ar.monitorId.value = m.id.value')
                ->andWhere('m.ownerId = :ownerId')
                ->setParameter('ownerId', $ownerId->value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
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

    public function findById(string $id): ?AlertRule
    {
        return $this->findOneBy(['id.value' => $id]);
    }
}
