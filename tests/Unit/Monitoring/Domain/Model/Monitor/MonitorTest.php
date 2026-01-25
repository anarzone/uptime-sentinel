<?php

declare(strict_types=1);

namespace App\Tests\Unit\Monitoring\Domain\Model\Monitor;

use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Model\Monitor\Url;
use PHPUnit\Framework\TestCase;

final class MonitorTest extends TestCase
{
    private MonitorId $monitorId;
    private Url $url;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->monitorId = MonitorId::generate();
        $this->url = Url::fromString('https://example.com');
        $this->now = new \DateTimeImmutable();
    }

    public function test_can_be_created_with_valid_parameters(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test Monitor',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: ['Authorization' => 'Bearer token'],
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+60 seconds'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $this->assertSame($this->monitorId, $monitor->id);
        $this->assertSame('Test Monitor', $monitor->name);
        $this->assertSame($this->url, $monitor->url);
        $this->assertSame(HttpMethod::GET, $monitor->method);
        $this->assertSame(60, $monitor->intervalSeconds);
        $this->assertSame(10, $monitor->timeoutSeconds);
        $this->assertSame(MonitorStatus::ACTIVE, $monitor->status);
        $this->assertSame(200, $monitor->expectedStatusCode);
        $this->assertSame(['Authorization' => 'Bearer token'], $monitor->headers);
        $this->assertNull($monitor->body);
        $this->assertNull($monitor->lastCheckedAt);
        $this->assertEquals($this->now->modify('+60 seconds'), $monitor->nextCheckAt);
        $this->assertEquals($this->now, $monitor->createdAt);
        $this->assertEquals($this->now, $monitor->updatedAt);
    }

    public function test_update_configuration_modifies_entity_in_place(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Original Name',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+60 seconds'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $newUrl = Url::fromString('https://updated.com');
        $originalUpdatedAt = $monitor->updatedAt;

        $monitor->updateConfiguration(
            name: 'Updated Name',
            url: $newUrl,
            method: HttpMethod::POST,
            intervalSeconds: 120,
            timeoutSeconds: 20,
            expectedStatusCode: 201,
            headers: ['X-Custom' => 'value'],
            body: '{"test":"data"}'
        );

        // Verify it's the same instance
        $this->assertSame($this->monitorId, $monitor->id);
        // Verify properties were updated in-place
        $this->assertSame('Updated Name', $monitor->name);
        $this->assertSame($newUrl, $monitor->url);
        $this->assertSame(HttpMethod::POST, $monitor->method);
        $this->assertSame(120, $monitor->intervalSeconds);
        $this->assertSame(20, $monitor->timeoutSeconds);
        $this->assertSame(201, $monitor->expectedStatusCode);
        $this->assertSame(['X-Custom' => 'value'], $monitor->headers);
        $this->assertSame('{"test":"data"}', $monitor->body);
        // Verify updatedAt was refreshed
        $this->assertGreaterThan($originalUpdatedAt, $monitor->updatedAt);
    }

    public function test_update_configuration_preserves_status(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::PAUSED,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+60 seconds'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $monitor->updateConfiguration(
            name: 'Updated',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            expectedStatusCode: 200,
            headers: null,
            body: null
        );

        $this->assertSame(MonitorStatus::PAUSED, $monitor->status);
    }

    public function test_update_configuration_preserves_id_and_created_at(): void
    {
        $createdAt = $this->now->modify('-1 day');

        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+60 seconds'),
            createdAt: $createdAt,
            updatedAt: $this->now
        );

        $monitor->updateConfiguration(
            name: 'Updated',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            expectedStatusCode: 200,
            headers: null,
            body: null
        );

        $this->assertSame($this->monitorId, $monitor->id);
        $this->assertEquals($createdAt, $monitor->createdAt);
    }

    public function test_pause_changes_status_to_paused(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+60 seconds'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $monitor->pause();

        $this->assertSame(MonitorStatus::PAUSED, $monitor->status);
    }

    public function test_resume_changes_status_to_active(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::PAUSED,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+60 seconds'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $monitor->resume();

        $this->assertSame(MonitorStatus::ACTIVE, $monitor->status);
    }

    public function test_mark_checked_updates_timestamps(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+60 seconds'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $checkedAt = $this->now->modify('+30 seconds');
        $monitor->markChecked($checkedAt, true);

        $this->assertEquals($checkedAt, $monitor->lastCheckedAt);
        $this->assertEquals($checkedAt->modify('+60 seconds'), $monitor->nextCheckAt);
        $this->assertEquals($checkedAt, $monitor->updatedAt);
    }

    public function test_mark_checked_throws_exception_for_inactive_monitor(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::PAUSED,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+60 seconds'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot mark check on inactive monitor');

        $monitor->markChecked($this->now, true);
    }

    public function test_is_due_returns_true_for_active_monitor_with_past_next_check(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('-1 minute'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $this->assertTrue($monitor->isDue());
    }

    public function test_is_due_returns_false_for_paused_monitor(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::PAUSED,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('-1 minute'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $this->assertFalse($monitor->isDue());
    }

    public function test_is_due_returns_false_for_future_next_check(): void
    {
        $monitor = new Monitor(
            id: $this->monitorId,
            name: 'Test',
            url: $this->url,
            method: HttpMethod::GET,
            intervalSeconds: 60,
            timeoutSeconds: 10,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: 200,
            headers: null,
            body: null,
            lastCheckedAt: null,
            nextCheckAt: $this->now->modify('+1 minute'),
            createdAt: $this->now,
            updatedAt: $this->now
        );

        $this->assertFalse($monitor->isDue());
    }
}
