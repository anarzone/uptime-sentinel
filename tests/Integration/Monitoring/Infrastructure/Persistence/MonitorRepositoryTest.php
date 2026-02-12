<?php

declare(strict_types=1);

namespace App\Tests\Integration\Monitoring\Infrastructure\Persistence;

use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Model\Monitor\Url;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Tests\Mother\MonitorMother;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MonitorRepositoryTest extends KernelTestCase
{

    private MonitorRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(MonitorRepositoryInterface::class);
    }

    public function test_can_save_and_find_monitor(): void
    {
        $monitor = MonitorMother::create();

        $this->repository->save($monitor);

        $foundMonitor = $this->repository->find($monitor->id);

        $this->assertInstanceOf(Monitor::class, $foundMonitor);
        $this->assertSame($monitor->id->toString(), $foundMonitor->id->toString());
        $this->assertSame($monitor->name, $foundMonitor->name);
        $this->assertSame($monitor->url->toString(), $foundMonitor->url->toString());
    }

    public function test_find_returns_null_for_non_existent_monitor(): void
    {
        $nonExistentId = MonitorId::generate();

        $monitor = $this->repository->find($nonExistentId);

        $this->assertNull($monitor);
    }

    public function test_find_all_returns_empty_array_when_no_monitors(): void
    {
        $monitors = $this->repository->findAll();

        $this->assertIsArray($monitors);
        $this->assertEmpty($monitors);
    }

    public function test_find_all_returns_all_monitors(): void
    {
        $monitor1 = MonitorMother::create(name: 'Monitor 1', url: 'https://example1.com');
        $monitor2 = MonitorMother::create(name: 'Monitor 2', url: 'https://example2.com');
        $monitor3 = MonitorMother::create(name: 'Monitor 3', url: 'https://example3.com');

        $this->repository->save($monitor1);
        $this->repository->save($monitor2);
        $this->repository->save($monitor3);

        $monitors = $this->repository->findAll();

        $this->assertCount(3, $monitors);
        $this->assertContainsOnlyInstancesOf(Monitor::class, $monitors);
    }

    public function test_find_by_status_returns_only_matching_monitors(): void
    {
        $activeMonitor1 = MonitorMother::create(status: MonitorStatus::ACTIVE);
        $activeMonitor2 = MonitorMother::create(status: MonitorStatus::ACTIVE);
        $pausedMonitor = MonitorMother::create(status: MonitorStatus::PAUSED);
        $disabledMonitor = MonitorMother::create(status: MonitorStatus::DISABLED);

        $this->repository->save($activeMonitor1);
        $this->repository->save($activeMonitor2);
        $this->repository->save($pausedMonitor);
        $this->repository->save($disabledMonitor);

        $activeMonitors = $this->repository->findByStatus(MonitorStatus::ACTIVE);
        $pausedMonitors = $this->repository->findByStatus(MonitorStatus::PAUSED);
        $disabledMonitors = $this->repository->findByStatus(MonitorStatus::DISABLED);

        $this->assertCount(2, $activeMonitors);
        $this->assertCount(1, $pausedMonitors);
        $this->assertCount(1, $disabledMonitors);
    }

    public function test_find_due_for_checking_returns_monitors_with_past_next_check_at(): void
    {
        $now = new \DateTimeImmutable();

        $pastMonitor = new Monitor(
            id: MonitorId::generate(),
            name: 'Past Monitor',
            url: Url::fromString('https://example1.com'),
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $now->modify('-1 minute'),
            createdAt: $now,
            updatedAt: $now
        );

        $dueMonitor = new Monitor(
            id: MonitorId::generate(),
            name: 'Due Monitor',
            url: Url::fromString('https://example2.com'),
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $now,
            createdAt: $now,
            updatedAt: $now
        );

        $futureMonitor = new Monitor(
            id: MonitorId::generate(),
            name: 'Future Monitor',
            url: Url::fromString('https://example3.com'),
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $now->modify('+1 minute'),
            createdAt: $now,
            updatedAt: $now
        );

        $this->repository->save($pastMonitor);
        $this->repository->save($dueMonitor);
        $this->repository->save($futureMonitor);

        $dueMonitors = $this->repository->findDueForChecking();

        $this->assertCount(2, $dueMonitors);

        $dueMonitorIds = array_map(fn($m) => $m->id->toString(), $dueMonitors);
        $this->assertContains($pastMonitor->id->toString(), $dueMonitorIds);
        $this->assertContains($dueMonitor->id->toString(), $dueMonitorIds);
        $this->assertNotContains($futureMonitor->id->toString(), $dueMonitorIds);
    }

    public function test_remove_deletes_monitor_from_repository(): void
    {
        $monitor = MonitorMother::create();

        $this->repository->save($monitor);
        $this->assertNotNull($this->repository->find($monitor->id));

        $this->repository->remove($monitor);
        $this->assertNull($this->repository->find($monitor->id));
    }

    public function test_exists_returns_true_for_existing_monitor(): void
    {
        $monitor = MonitorMother::create();

        $this->repository->save($monitor);

        $this->assertTrue($this->repository->exists($monitor->id));
    }

    public function test_exists_returns_false_for_non_existent_monitor(): void
    {
        $nonExistentId = MonitorId::generate();

        $this->assertFalse($this->repository->exists($nonExistentId));
    }

    public function test_can_update_existing_monitor(): void
    {
        // Create monitor
        $monitor = MonitorMother::create(name: 'Original Name');
        $this->repository->save($monitor);

        // Update configuration in-place (mutable entity)
        $monitor->updateConfiguration(
            name: 'Updated Name',
            url: $monitor->url,
            method: $monitor->method,
            intervalSeconds: $monitor->intervalSeconds,
            timeoutSeconds: $monitor->timeoutSeconds,
            expectedStatusCode: $monitor->expectedStatusCode,
            headers: $monitor->headers,
            body: $monitor->body
        );

        $this->repository->save($monitor);

        // Clear to ensure fresh fetch from database
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->clear();

        $foundMonitor = $this->repository->find($monitor->id);

        $this->assertSame('Updated Name', $foundMonitor->name);
    }

    public function test_saved_monitor_preserves_all_properties(): void
    {
        $now = new \DateTimeImmutable();

        $monitor = new Monitor(
            id: MonitorId::generate(),
            name: 'Complex Monitor',
            url: Url::fromString('https://api.example.com/v1/health'),
            method: HttpMethod::POST,
            intervalSeconds: 300,
            timeoutSeconds: 30,
            status: MonitorStatus::PAUSED,
            expectedStatusCode: 201,
            headers: ['Authorization' => 'Bearer token123', 'Content-Type' => 'application/json'],
            body: '{"test": "data"}',
            lastCheckedAt: $now->modify('-5 minutes'),
            nextCheckAt: $now->modify('+5 minutes'),
            createdAt: $now->modify('-1 hour'),
            updatedAt: $now
        );

        $this->repository->save($monitor);

        $foundMonitor = $this->repository->find($monitor->id);

        $this->assertSame($monitor->id->toString(), $foundMonitor->id->toString());
        $this->assertSame('Complex Monitor', $foundMonitor->name);
        $this->assertSame('https://api.example.com/v1/health', $foundMonitor->url->toString());
        $this->assertSame(HttpMethod::POST, $foundMonitor->method);
        $this->assertSame(300, $foundMonitor->intervalSeconds);
        $this->assertSame(30, $foundMonitor->timeoutSeconds);
        $this->assertSame(MonitorStatus::PAUSED, $foundMonitor->status);
        $this->assertSame(201, $foundMonitor->expectedStatusCode);
        $this->assertSame(['Authorization' => 'Bearer token123', 'Content-Type' => 'application/json'], $foundMonitor->headers);
        $this->assertSame('{"test": "data"}', $foundMonitor->body);
    }
}
