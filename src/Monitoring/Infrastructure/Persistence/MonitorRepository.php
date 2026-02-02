<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MonitorRepository extends ServiceEntityRepository implements MonitorRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Monitor::class);
    }
    public function findPaginated(int $page, int $limit): array
    {
        return $this->createQueryBuilder('m')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id.value)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findDueForChecking(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.nextCheckAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function findDueMonitors(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.nextCheckAt <= :now')
            ->andWhere('m.status = :status')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', MonitorStatus::ACTIVE)
            ->getQuery()
            ->execute();
    }

    public function findByStatus(MonitorStatus $status): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->execute();
    }

    /**
     * @param string[] $ids
     *
     * @return Monitor[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('m')
            ->where('m.id.value IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    public function save(Monitor $monitor): void
    {
        $this->getEntityManager()->persist($monitor);
        $this->getEntityManager()->flush();
    }

    public function remove(Monitor $monitor): void
    {
        $this->getEntityManager()->remove($monitor);
        $this->getEntityManager()->flush();
    }

    public function exists(MonitorId $id): bool
    {
        return $this->find($id->toString()) !== null;
    }
}
