<?php

declare(strict_types=1);

namespace App\Tests\Mother;

use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Model\Monitor\Url;
use Symfony\Component\Uid\UuidV7;

use App\Monitoring\Domain\ValueObject\OwnerId;

/**
 * Test factory for creating Monitor entities and value objects.
 *
 * This Mother class provides convenient factory methods for creating
 * test data with sensible defaults while allowing customization.
 */
final class MonitorMother
{
    private const DEFAULT_NAME = 'Test Monitor';
    private const DEFAULT_URL = 'https://example.com';
    private const DEFAULT_INTERVAL_SECONDS = 60;
    private const DEFAULT_TIMEOUT_SECONDS = 10;
    private const DEFAULT_EXPECTED_STATUS_CODE = 200;

    public static function create(
        ?string $name = null,
        ?string $url = null,
        ?HttpMethod $method = null,
        ?int $intervalSeconds = null,
        ?int $timeoutSeconds = null,
        ?MonitorStatus $status = null,
        ?int $expectedStatusCode = null,
        ?array $headers = null,
        ?string $body = null,
        ?MonitorId $id = null,
        ?OwnerId $ownerId = null,
    ): Monitor {
        $now = new \DateTimeImmutable();
        $interval = $intervalSeconds ?? self::DEFAULT_INTERVAL_SECONDS;

        return new Monitor(
            id: $id ?? self::createMonitorId(),
            name: $name ?? self::DEFAULT_NAME,
            url: self::createUrl($url ?? self::DEFAULT_URL),
            method: $method ?? HttpMethod::GET,
            intervalSeconds: $interval,
            timeoutSeconds: $timeoutSeconds ?? self::DEFAULT_TIMEOUT_SECONDS,
            status: $status ?? MonitorStatus::ACTIVE,
            expectedStatusCode: $expectedStatusCode ?? self::DEFAULT_EXPECTED_STATUS_CODE,
            headers: $headers,
            body: $body,
            lastCheckedAt: null,
            nextCheckAt: $now->modify("+{$interval} seconds"),
            createdAt: $now,
            updatedAt: $now,
            ownerId: $ownerId
        );
    }

    /**
     * Create a MonitorId with a specific or random UUID v7.
     */
    public static function createMonitorId(?string $uuid = null): MonitorId
    {
        return MonitorId::fromString($uuid ?? (new UuidV7())->toRfc4122());
    }

    /**
     * Create a Url value object.
     */
    public static function createUrl(?string $url = null): Url
    {
        return Url::fromString($url ?? self::DEFAULT_URL);
    }

    /**
     * Create a random MonitorId for testing.
     */
    public static function random(): Monitor
    {
        return self::create(
            name: 'Monitor ' . rand(1, 1000),
            url: 'https://' . rand(1, 1000) . '.example.com',
            method: HttpMethod::cases()[array_rand(HttpMethod::cases())],
            intervalSeconds: rand(30, 300),
            timeoutSeconds: rand(5, 30),
            status: MonitorStatus::cases()[array_rand(MonitorStatus::cases())],
            expectedStatusCode: rand(200, 299)
        );
    }
}
