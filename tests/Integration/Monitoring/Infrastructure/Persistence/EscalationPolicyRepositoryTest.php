<?php

declare(strict_types=1);

namespace App\Tests\Integration\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use App\Monitoring\Domain\Model\Alert\EscalationPolicy;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

final class EscalationPolicyRepositoryTest extends KernelTestCase
{
    use ResetDatabase;

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

        // 2. Create Specific Policies
        $monitorId = MonitorId::generate();
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

        // 3. Create another Specific Policy for a different monitor
        $otherMonitorId = MonitorId::generate();
        $otherPolicy = EscalationPolicy::create(
            $otherMonitorId,
            1,
            5,
            $channel
        );

        $this->repository->save($policy1);
        $this->repository->save($policy2);
        $this->repository->save($otherPolicy);

        // 4. Query for first monitor
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
