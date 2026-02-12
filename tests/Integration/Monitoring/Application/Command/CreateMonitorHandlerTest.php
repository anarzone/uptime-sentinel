<?php

declare(strict_types=1);

namespace App\Tests\Integration\Monitoring\Application\Command;

use App\Monitoring\Application\Command\CreateMonitor\CreateMonitorCommand;
use App\Monitoring\Application\Command\CreateMonitor\CreateMonitorHandler;
use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateMonitorHandlerTest extends KernelTestCase
{

    private CreateMonitorHandler $handler;
    private MonitorRepositoryInterface $repository;
    private string $testRequesterId = '01923b48-7807-72d2-8705-836e0b7c1e7a';

    protected function setUp(): void
    {
        self::bootKernel();

        $this->handler = self::getContainer()->get(CreateMonitorHandler::class);
        $this->repository = self::getContainer()->get(MonitorRepositoryInterface::class);
    }

    public function test_creates_monitor_with_valid_data(): void
    {
        $uuid = '01923b48-7807-72d2-8705-836e0b7c1e7e';

        $command = new CreateMonitorCommand(
            uuid: $uuid,
            name: 'Test Monitor',
            url: 'https://example.com',
            method: 'GET',
            intervalSeconds: 60,
            timeoutSeconds: 10,
            expectedStatusCode: 200,
            headers: ['Authorization' => 'Bearer token'],
            body: null,
            requesterId: $this->testRequesterId,
            ownerId: $this->testRequesterId
        );

        ($this->handler)($command);

        $monitor = $this->repository->find(MonitorId::fromString($uuid));

        $this->assertInstanceOf(Monitor::class, $monitor);
        $this->assertSame('Test Monitor', $monitor->name);
        $this->assertSame('https://example.com', $monitor->url->toString());
        $this->assertSame(HttpMethod::GET, $monitor->method);
        $this->assertSame(60, $monitor->intervalSeconds);
        $this->assertSame(10, $monitor->timeoutSeconds);
        $this->assertSame(MonitorStatus::ACTIVE, $monitor->status);
        $this->assertSame(200, $monitor->expectedStatusCode);
        $this->assertSame(['Authorization' => 'Bearer token'], $monitor->headers);
        $this->assertNull($monitor->body);
        $this->assertSame($this->testRequesterId, $monitor->ownerId);
    }

    public function test_creates_monitor_with_all_fields(): void
    {
        $uuid = '01923b48-7807-72d2-8705-836e0b7c1e7e';

        $command = new CreateMonitorCommand(
            uuid: $uuid,
            name: 'Full Monitor',
            url: 'https://api.example.com/v1/health',
            method: 'POST',
            intervalSeconds: 120,
            timeoutSeconds: 30,
            expectedStatusCode: 201,
            headers: ['Content-Type' => 'application/json'],
            body: '{"test": "data"}',
            requesterId: $this->testRequesterId,
            ownerId: $this->testRequesterId
        );

        ($this->handler)($command);

        $monitor = $this->repository->find(MonitorId::fromString($uuid));

        $this->assertInstanceOf(Monitor::class, $monitor);
        $this->assertSame('Full Monitor', $monitor->name);
        $this->assertSame('https://api.example.com/v1/health', $monitor->url->toString());
        $this->assertSame(HttpMethod::POST, $monitor->method);
        $this->assertSame(120, $monitor->intervalSeconds);
        $this->assertSame(30, $monitor->timeoutSeconds);
        $this->assertSame(MonitorStatus::ACTIVE, $monitor->status);
        $this->assertSame(201, $monitor->expectedStatusCode);
        $this->assertSame(['Content-Type' => 'application/json'], $monitor->headers);
        $this->assertSame('{"test": "data"}', $monitor->body);
    }

    public function test_sets_next_check_at_based_on_interval(): void
    {
        $uuid = '01923b48-7807-72d2-8705-836e0b7c1e7e';
        $before = new \DateTimeImmutable();

        $command = new CreateMonitorCommand(
            uuid: $uuid,
            name: 'Test Monitor',
            url: 'https://example.com',
            method: 'GET',
            intervalSeconds: 300,
            timeoutSeconds: 10,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            requesterId: $this->testRequesterId,
            ownerId: $this->testRequesterId
        );

        ($this->handler)($command);

        $monitor = $this->repository->find(MonitorId::fromString($uuid));
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before->modify('+300 seconds')->getTimestamp(), $monitor->nextCheckAt->getTimestamp());
        $this->assertLessThanOrEqual($after->modify('+300 seconds')->getTimestamp(), $monitor->nextCheckAt->getTimestamp());
    }

    public function test_created_monitor_has_null_last_checked_at(): void
    {
        $uuid = '01923b48-7807-72d2-8705-836e0b7c1e7e';

        $command = new CreateMonitorCommand(
            uuid: $uuid,
            name: 'Test Monitor',
            url: 'https://example.com',
            method: 'GET',
            intervalSeconds: 60,
            timeoutSeconds: 10,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            requesterId: $this->testRequesterId,
            ownerId: $this->testRequesterId
        );

        ($this->handler)($command);

        $monitor = $this->repository->find(MonitorId::fromString($uuid));

        $this->assertNull($monitor->lastCheckedAt);
    }

    public function test_sets_created_and_updated_at(): void
    {
        $uuid = '01923b48-7807-72d2-8705-836e0b7c1e7e';
        $before = new \DateTimeImmutable();

        $command = new CreateMonitorCommand(
            uuid: $uuid,
            name: 'Test Monitor',
            url: 'https://example.com',
            method: 'GET',
            intervalSeconds: 60,
            timeoutSeconds: 10,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            requesterId: $this->testRequesterId,
            ownerId: $this->testRequesterId
        );

        ($this->handler)($command);

        $monitor = $this->repository->find(MonitorId::fromString($uuid));
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $monitor->createdAt);
        $this->assertLessThanOrEqual($after, $monitor->createdAt);
        $this->assertGreaterThanOrEqual($before, $monitor->updatedAt);
        $this->assertLessThanOrEqual($after, $monitor->updatedAt);
    }

    public function test_can_create_multiple_monitors(): void
    {
        $command1 = new CreateMonitorCommand(
            uuid: '01923b48-7807-72d2-8705-836e0b7c1e7e',
            name: 'Monitor 1',
            url: 'https://example1.com',
            method: 'GET',
            intervalSeconds: 60,
            timeoutSeconds: 10,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            requesterId: $this->testRequesterId,
            ownerId: $this->testRequesterId
        );

        $command2 = new CreateMonitorCommand(
            uuid: '01923b48-7807-72d2-8705-836e0b7c1e7f',
            name: 'Monitor 2',
            url: 'https://example2.com',
            method: 'GET',
            intervalSeconds: 120,
            timeoutSeconds: 20,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            requesterId: $this->testRequesterId,
            ownerId: $this->testRequesterId
        );

        ($this->handler)($command1);
        ($this->handler)($command2);

        $monitors = $this->repository->findAll();

        $this->assertCount(2, $monitors);
    }
}
