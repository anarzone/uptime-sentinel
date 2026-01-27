<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence\Fixtures;

use App\Monitoring\Domain\Model\Alert\NotificationType;
use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Infrastructure\Persistence\MonitorRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\UuidV7;

/**
 * Comprehensive alert rule fixture covering various scenarios.
 *
 * Creates ~100 alert rules with different combinations of:
 * - Failure thresholds (1, 3, 5, 10)
 * - Notification types (FAILURE, RECOVERY, BOTH)
 * - Channels (Email, Slack, Webhook)
 * - Cooldown intervals (30min, 1hr, 6hr)
 * - Monitor types (active, paused, POST, PUT, different status codes, intervals)
 */
class AlertRuleFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private MonitorRepository $monitorRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof \Doctrine\ORM\EntityManagerInterface) {
            throw new \RuntimeException('Fixture requires an EntityManager');
        }

        $connection = $manager->getConnection();

        // Fetch notification channel IDs
        $channels = $this->getChannelIds($connection);
        if (empty($channels)) {
            echo "No notification channels found. Skipping alert rule creation.\n";

            return;
        }

        // Fetch monitors
        $monitors = $this->monitorRepository->findAll();
        if (empty($monitors)) {
            echo "No monitors found. Skipping alert rule creation.\n";

            return;
        }

        echo 'Creating alert rules for '.\count($monitors)." monitors...\n";

        // Group monitors by characteristics
        $monitorGroups = $this->groupMonitors($monitors);

        $rulesCreated = 0;

        // 1. Varying Failure Thresholds (use first 10 active monitors)
        $rulesCreated += $this->createThresholdRules(
            $connection,
            $monitorGroups['active'],
            $channels['email'],
            10
        );

        // 2. Varying Notification Types (use monitors 11-25)
        $rulesCreated += $this->createNotificationTypeRules(
            $connection,
            $monitorGroups['active'],
            $channels['email'],
            15,
            10
        );

        // 3. Varying Channels (use monitors 26-55)
        $rulesCreated += $this->createChannelRules(
            $connection,
            $monitorGroups['active'],
            $channels,
            30,
            25
        );

        // 4. Varying Cooldown Intervals (use monitors 56-65)
        $rulesCreated += $this->createCooldownRules(
            $connection,
            $monitorGroups['active'],
            $channels['slack'],
            10,
            55
        );

        // 5. Different Monitor Types
        // Paused monitors
        $rulesCreated += $this->createTypeRules(
            $connection,
            $monitorGroups['paused'],
            $channels['email'],
            3,
            NotificationType::FAILURE
        );

        // POST method monitors
        $rulesCreated += $this->createTypeRules(
            $connection,
            $monitorGroups['post'],
            $channels['slack'],
            3,
            NotificationType::BOTH
        );

        // PUT method monitors
        $rulesCreated += $this->createTypeRules(
            $connection,
            $monitorGroups['put'],
            $channels['webhook'],
            3,
            NotificationType::FAILURE
        );

        // Status 201 monitors
        $rulesCreated += $this->createTypeRules(
            $connection,
            $monitorGroups['status201'],
            $channels['email'],
            3,
            NotificationType::BOTH
        );

        // Fast interval monitors (30s)
        $rulesCreated += $this->createTypeRules(
            $connection,
            $monitorGroups['fast'],
            $channels['slack'],
            3,
            NotificationType::FAILURE
        );

        // Slow interval monitors (300s)
        $rulesCreated += $this->createTypeRules(
            $connection,
            $monitorGroups['slow'],
            $channels['webhook'],
            3,
            NotificationType::RECOVERY
        );

        echo "Alert rule fixtures loaded successfully. Total rules created: {$rulesCreated}\n";
    }

    public function getDependencies(): array
    {
        return [
            MonitorFixture::class,
            NotificationChannelFixture::class,
        ];
    }

    /**
     * Get notification channel IDs from database.
     *
     * @return array<string, string> Array of channel type => UUID
     */
    private function getChannelIds(Connection $connection): array
    {
        $sql = 'SELECT uuid, type FROM notification_channels';
        $result = $connection->executeQuery($sql);
        $rows = $result->fetchAllAssociative();

        $channels = [];
        foreach ($rows as $row) {
            $channels[$row['type']] = $row['uuid'];
        }

        return $channels;
    }

    /**
     * Group monitors by characteristics.
     *
     * @param array<Monitor> $monitors
     *
     * @return array<string, array<Monitor>>
     */
    private function groupMonitors(array $monitors): array
    {
        $groups = [
            'active' => [],
            'paused' => [],
            'post' => [],
            'put' => [],
            'status201' => [],
            'fast' => [],
            'slow' => [],
        ];

        $count = 0;
        foreach ($monitors as $monitor) {
            // Collect first 1000 active monitors for rule creation
            if ($monitor->status === MonitorStatus::ACTIVE && $count < 1000) {
                $groups['active'][] = $monitor;
                ++$count;
            }

            if ($monitor->status === MonitorStatus::PAUSED) {
                $groups['paused'][] = $monitor;
            }

            if ($monitor->method === HttpMethod::POST) {
                $groups['post'][] = $monitor;
            }

            if ($monitor->method === HttpMethod::PUT) {
                $groups['put'][] = $monitor;
            }

            if ($monitor->expectedStatusCode === 201) {
                $groups['status201'][] = $monitor;
            }

            if ($monitor->intervalSeconds === 30) {
                $groups['fast'][] = $monitor;
            }

            if ($monitor->intervalSeconds === 300) {
                $groups['slow'][] = $monitor;
            }
        }

        return $groups;
    }

    /**
     * Create rules with varying failure thresholds.
     *
     * @param array<Monitor> $monitors
     */
    private function createThresholdRules(
        Connection $connection,
        array $monitors,
        string $channelId,
        int $maxMonitors
    ): int {
        $thresholds = [1, 3, 3, 3, 5, 5, 5, 10]; // Distribution of thresholds
        $created = 0;
        $monitorIdx = 0;

        foreach ($thresholds as $threshold) {
            if ($monitorIdx >= $maxMonitors || $monitorIdx >= \count($monitors)) {
                break;
            }

            $monitor = $monitors[$monitorIdx];
            $this->insertAlertRule(
                $connection,
                $monitor->id->toString(),
                $channelId,
                NotificationType::FAILURE,
                $threshold,
                null
            );

            ++$created;
            ++$monitorIdx;
        }

        echo "  - Created {$created} rules with varying thresholds\n";

        return $created;
    }

    /**
     * Create rules with varying notification types.
     *
     * @param array<Monitor> $monitors
     */
    private function createNotificationTypeRules(
        Connection $connection,
        array $monitors,
        string $channelId,
        int $maxMonitors,
        int $offset
    ): int {
        $types = [
            NotificationType::FAILURE,
            NotificationType::FAILURE,
            NotificationType::FAILURE,
            NotificationType::FAILURE,
            NotificationType::FAILURE,
            NotificationType::RECOVERY,
            NotificationType::RECOVERY,
            NotificationType::RECOVERY,
            NotificationType::RECOVERY,
            NotificationType::RECOVERY,
            NotificationType::BOTH,
            NotificationType::BOTH,
            NotificationType::BOTH,
            NotificationType::BOTH,
            NotificationType::BOTH,
        ];

        $created = 0;
        $monitorIdx = $offset;

        foreach ($types as $type) {
            if ($monitorIdx >= $maxMonitors || $monitorIdx >= \count($monitors)) {
                break;
            }

            $monitor = $monitors[$monitorIdx];
            $this->insertAlertRule(
                $connection,
                $monitor->id->toString(),
                $channelId,
                $type,
                3, // default threshold
                null
            );

            ++$created;
            ++$monitorIdx;
        }

        echo "  - Created {$created} rules with varying notification types\n";

        return $created;
    }

    /**
     * Create rules with varying channels.
     *
     * @param array<Monitor>        $monitors
     * @param array<string, string> $channels
     */
    private function createChannelRules(
        Connection $connection,
        array $monitors,
        array $channels,
        int $maxMonitors,
        int $offset
    ): int {
        $channelTypes = array_keys($channels);
        $created = 0;
        $monitorIdx = $offset;

        for ($i = 0; $i < 30; ++$i) {
            if ($monitorIdx >= $maxMonitors || $monitorIdx >= \count($monitors)) {
                break;
            }

            $monitor = $monitors[$monitorIdx];
            $channelType = $channelTypes[$i % \count($channelTypes)];
            $channelId = $channels[$channelType];

            $this->insertAlertRule(
                $connection,
                $monitor->id->toString(),
                $channelId,
                NotificationType::FAILURE,
                3,
                null
            );

            ++$created;
            ++$monitorIdx;
        }

        echo "  - Created {$created} rules across all channels\n";

        return $created;
    }

    /**
     * Create rules with varying cooldown intervals.
     *
     * @param array<Monitor> $monitors
     */
    private function createCooldownRules(
        Connection $connection,
        array $monitors,
        string $channelId,
        int $maxMonitors,
        int $offset
    ): int {
        $cooldowns = ['PT30M', 'PT30M', 'PT1H', 'PT1H', 'PT6H'];
        $created = 0;
        $monitorIdx = $offset;

        foreach ($cooldowns as $cooldown) {
            if ($monitorIdx >= $maxMonitors || $monitorIdx >= \count($monitors)) {
                break;
            }

            $monitor = $monitors[$monitorIdx];
            $this->insertAlertRule(
                $connection,
                $monitor->id->toString(),
                $channelId,
                NotificationType::FAILURE,
                3,
                $cooldown
            );

            ++$created;
            ++$monitorIdx;
        }

        echo "  - Created {$created} rules with varying cooldown intervals\n";

        return $created;
    }

    /**
     * Create rules for specific monitor types.
     *
     * @param array<Monitor> $monitors
     */
    private function createTypeRules(
        Connection $connection,
        array $monitors,
        string $channelId,
        int $count,
        NotificationType $type
    ): int {
        $created = 0;

        foreach ($monitors as $monitor) {
            if ($created >= $count) {
                break;
            }

            $this->insertAlertRule(
                $connection,
                $monitor->id->toString(),
                $channelId,
                $type,
                3,
                null
            );

            ++$created;
        }

        $typeName = $type->value;

        echo "  - Created {$created} rules for monitor type (type={$typeName})\n";

        return $created;
    }

    /**
     * Insert a single alert rule using raw SQL.
     */
    private function insertAlertRule(
        Connection $connection,
        string $monitorId,
        string $channelId,
        NotificationType $type,
        int $failureThreshold,
        ?string $cooldownInterval
    ): void {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $uuid = (new UuidV7())->toRfc4122();

        $sql = 'INSERT INTO alert_rules (
            uuid,
            monitor_id_uuid,
            notification_channel_id,
            type,
            failure_threshold,
            cooldown_interval,
            is_enabled,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $connection->executeStatement($sql, [
            $uuid,
            $monitorId,
            $channelId,
            $type->value,
            $failureThreshold,
            $cooldownInterval,
            1, // is_enabled
            $now,
        ]);
    }
}
