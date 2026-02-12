<?php

declare(strict_types=1);

namespace App\Tests\Integration\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use App\Monitoring\Domain\Model\Alert\EscalationPolicy;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EscalationPolicyRepositoryTest extends KernelTestCase
{

    private EscalationPolicyRepositoryInterface $repository;
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(EscalationPolicyRepositoryInterface::class);
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    public function test_find_applicable_for_monitor_returns_specific_policies(): void
    {
        // 1. Create a Notification Channel (needed for policies)
        $channel = NotificationChannel::create(
            'Test Channel',
            NotificationChannelType::EMAIL,
            'test@example.com'
        );
        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        // 2. Create a Monitor (required for policies)
        $monitorId = MonitorId::generate();
        $monitor = new \App\Monitoring\Domain\Model\Monitor\Monitor(
            $monitorId,
            'Test Monitor',
            \App\Monitoring\Domain\Model\Monitor\Url::fromString('https://example.com'),
            \App\Monitoring\Domain\Model\Monitor\HttpMethod::GET,
            60,
            10,
            \App\Monitoring\Domain\Model\Monitor\MonitorStatus::ACTIVE,
            200,
            null,
            null,
            null,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
        $this->entityManager->persist($monitor);
        $this->entityManager->flush();

        // 3. Create Specific Policies
        $policy1 = EscalationPolicy::create(
            $monitorId,
            1,
            3,
            $channel
        );
        $policy2 = EscalationPolicy::create(
            $monitorId,
            2,
            10,
            $channel
        );

        // 4. Create another Monitor and Policy for it
        $otherMonitorId = MonitorId::generate();
        $otherMonitor = new \App\Monitoring\Domain\Model\Monitor\Monitor(
            $otherMonitorId,
            'Other Monitor',
            \App\Monitoring\Domain\Model\Monitor\Url::fromString('https://other.com'),
            \App\Monitoring\Domain\Model\Monitor\HttpMethod::GET,
            60,
            10,
            \App\Monitoring\Domain\Model\Monitor\MonitorStatus::ACTIVE,
            200,
            null,
            null,
            null,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
        $this->entityManager->persist($otherMonitor);
        $this->entityManager->flush();

        $otherPolicy = EscalationPolicy::create(
            $otherMonitorId,
            1,
            5,
            $channel
        );

        $this->repository->save($policy1);
        $this->repository->save($policy2);
        $this->repository->save($otherPolicy);

        // 5. Query for first monitor
        $policies = $this->repository->findApplicableForMonitor($monitorId);

        $this->assertCount(2, $policies);

        $policyIds = array_map(fn($p) => $p->id->toString(), $policies);
        $this->assertContains($policy1->id->toString(), $policyIds);
        $this->assertContains($policy2->id->toString(), $policyIds);
        $this->assertNotContains($otherPolicy->id->toString(), $policyIds);

        // 5. Verify sort order (by Level ASC)
        $this->assertSame($policy1->id->toString(), $policies[0]->id->toString());
        $this->assertSame($policy2->id->toString(), $policies[1]->id->toString());
    }

    public function test_find_applicable_for_monitor_returns_only_enabled_policies(): void
    {
        $channel = NotificationChannel::create(
            'Test Channel',
            NotificationChannelType::EMAIL,
            'test@example.com'
        );
        $this->entityManager->persist($channel);
        $this->entityManager->flush();

        $monitorId = MonitorId::generate();
        $monitor = new \App\Monitoring\Domain\Model\Monitor\Monitor(
            $monitorId,
            'Test Monitor',
            \App\Monitoring\Domain\Model\Monitor\Url::fromString('https://example.com'),
            \App\Monitoring\Domain\Model\Monitor\HttpMethod::GET,
            60,
            10,
            \App\Monitoring\Domain\Model\Monitor\MonitorStatus::ACTIVE,
            200,
            null,
            null,
            null,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
        $this->entityManager->persist($monitor);
        $this->entityManager->flush();

        $enabledPolicy = EscalationPolicy::create($monitorId, 1, 3, $channel);
        $disabledPolicy = EscalationPolicy::create($monitorId, 2, 10, $channel);
        $disabledPolicy->disable();

        $this->repository->save($enabledPolicy);
        $this->repository->save($disabledPolicy);

        $policies = $this->repository->findApplicableForMonitor($monitorId);

        $this->assertCount(1, $policies);
        $this->assertSame($enabledPolicy->id->toString(), $policies[0]->id->toString());
    }
}
