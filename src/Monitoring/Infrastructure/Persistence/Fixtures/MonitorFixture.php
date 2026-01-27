<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence\Fixtures;

use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\UuidV7;

/**
 * High-performance fixture loader using raw SQL bulk inserts.
 *
 * This fixture bypasses Doctrine ORM for better performance and memory efficiency
 * when inserting large datasets (10,000+ monitors). Uses prepared statements
 * with batch inserts to minimize memory overhead.
 */
final class MonitorFixture extends Fixture
{
    private const BATCH_SIZE = 1000;
    private const ACTIVE_COUNT = 10000;
    private const PAUSED_COUNT = 250;

    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof \Doctrine\ORM\EntityManagerInterface) {
            throw new \RuntimeException('Fixture requires an EntityManager');
        }

        $connection = $manager->getConnection();

        echo 'Creating '.self::ACTIVE_COUNT." active monitors...\n";
        $this->insertMonitors(
            $connection,
            self::ACTIVE_COUNT,
            'Active Monitor',
            'https://example.com/site-',
            MonitorStatus::ACTIVE,
            HttpMethod::GET,
            60,
            200
        );

        echo 'Creating '.self::PAUSED_COUNT." paused monitors...\n";
        $this->insertMonitors(
            $connection,
            self::PAUSED_COUNT,
            'Paused Monitor',
            'https://example.com/paused-',
            MonitorStatus::PAUSED,
            HttpMethod::GET,
            60,
            200
        );

        echo "Creating monitors with varied configurations...\n";
        $variations = [
            ['method' => HttpMethod::POST, 'status' => MonitorStatus::ACTIVE, 'count' => 50, 'urlPrefix' => 'https://example.com/post-'],
            ['method' => HttpMethod::PUT, 'status' => MonitorStatus::ACTIVE, 'count' => 50, 'urlPrefix' => 'https://example.com/put-'],
            ['method' => HttpMethod::GET, 'status' => MonitorStatus::ACTIVE, 'count' => 50, 'urlPrefix' => 'https://example.com/status201-', 'expectedStatusCode' => 201],
            ['method' => HttpMethod::GET, 'status' => MonitorStatus::ACTIVE, 'count' => 50, 'urlPrefix' => 'https://example.com/fast-', 'intervalSeconds' => 30],
            ['method' => HttpMethod::GET, 'status' => MonitorStatus::ACTIVE, 'count' => 50, 'urlPrefix' => 'https://example.com/slow-', 'intervalSeconds' => 300],
        ];

        foreach ($variations as $idx => $variation) {
            $this->insertMonitors(
                $connection,
                $variation['count'],
                'Variant '.($idx + 1),
                $variation['urlPrefix'],
                $variation['status'],
                $variation['method'],
                $variation['intervalSeconds'] ?? 60,
                $variation['expectedStatusCode'] ?? 200
            );
        }

        echo "Monitor fixtures loaded successfully.\n";
    }

    /**
     * Insert monitors in batches using raw SQL for optimal performance.
     */
    private function insertMonitors(
        Connection $connection,
        int $count,
        string $namePrefix,
        string $urlPrefix,
        MonitorStatus $status,
        HttpMethod $method,
        int $intervalSeconds,
        int $expectedStatusCode
    ): void {
        $now = new \DateTimeImmutable();
        $nextCheckAt = $now->modify("+$intervalSeconds seconds");
        $timeoutSeconds = 10;

        // Format dates as strings for SQL
        $nextCheckAtStr = $nextCheckAt->format('Y-m-d H:i:s');
        $nowStr = $now->format('Y-m-d H:i:s');

        // Build bulk insert statement with placeholders
        $placeholders = [];
        $values = [];
        $batchCount = 0;

        for ($i = 0; $i < $count; ++$i) {
            $uuid = (new UuidV7())->toRfc4122();

            // 16 parameters per row
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            // Flat array of values
            $values[] = $uuid;                        // uuid
            $values[] = "$namePrefix #$i";            // name
            $values[] = "$urlPrefix$i";               // url_value
            $values[] = $method->value;               // method
            $values[] = $intervalSeconds;             // interval_seconds
            $values[] = $timeoutSeconds;              // timeout_seconds
            $values[] = $status->value;               // status
            $values[] = $expectedStatusCode;          // expected_status_code
            $values[] = 'unknown';                    // health_status
            $values[] = null;                         // last_status_change_at
            $values[] = null;                         // headers (JSON or null)
            $values[] = null;                         // body
            $values[] = null;                         // last_checked_at
            $values[] = $nextCheckAtStr;             // next_check_at
            $values[] = $nowStr;                     // created_at
            $values[] = $nowStr;                     // updated_at

            ++$batchCount;

            // Execute batch when reaching batch size
            if ($batchCount >= self::BATCH_SIZE) {
                $this->executeBulkInsert($connection, $placeholders, $values);
                $placeholders = [];
                $values = [];
                $batchCount = 0;
                echo "...inserted $i monitors\n";
            }
        }

        // Insert remaining records
        if (!empty($placeholders)) {
            $this->executeBulkInsert($connection, $placeholders, $values);
        }
    }

    /**
     * Execute a bulk insert statement.
     */
    private function executeBulkInsert(
        Connection $connection,
        array $placeholders,
        array $values
    ): void {
        $sql = 'INSERT INTO monitors (
            uuid, name, url_value, method, interval_seconds, timeout_seconds,
            status, expected_status_code, health_status, last_status_change_at, 
            headers, body, last_checked_at, next_check_at, created_at, updated_at
        ) VALUES '.implode(', ', $placeholders);

        $connection->executeStatement($sql, $values);
    }
}
