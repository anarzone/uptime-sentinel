<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence\Fixtures;

use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Alert\NotificationType;
use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Infrastructure\Persistence\MonitorRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

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
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Fixture requires an EntityManager');
        }

        // Fetch notification channels
        $channels = $this->getChannels($manager);
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
            $manager,
            $monitorGroups['active'],
            $channels['email'],
            10
        );

        // 2. Varying Notification Types (use monitors 11-25)
        $rulesCreated += $this->createNotificationTypeRules(
            $manager,
            $monitorGroups['active'],
            $channels['email'],
            15,
            10
        );

        // 3. Varying Channels (use monitors 26-55)
        $rulesCreated += $this->createChannelRules(
            $manager,
            $monitorGroups['active'],
            $channels,
            30,
            25
        );

        // 4. Varying Cooldown Intervals (use monitors 56-65)
        $rulesCreated += $this->createCooldownRules(
            $manager,
            $monitorGroups['active'],
            $channels['slack'],
            10,
            55
        );

        // 5. Different Monitor Types
        // Paused monitors
        $rulesCreated += $this->createTypeRules(
            $manager,
            $monitorGroups['paused'],
            $channels['email'],
            3,
            NotificationType::FAILURE
        );

        // POST method monitors
        $rulesCreated += $this->createTypeRules(
            $manager,
            $monitorGroups['post'],
            $channels['slack'],
            3,
            NotificationType::BOTH
        );

        // PUT method monitors
        $rulesCreated += $this->createTypeRules(
            $manager,
            $monitorGroups['put'],
            $channels['webhook'],
            3,
            NotificationType::FAILURE
        );

        // Status 201 monitors
        $rulesCreated += $this->createTypeRules(
            $manager,
            $monitorGroups['status201'],
            $channels['email'],
            3,
            NotificationType::BOTH
        );

        // Fast interval monitors (30s)
        $rulesCreated += $this->createTypeRules(
            $manager,
            $monitorGroups['fast'],
            $channels['slack'],
            3,
            NotificationType::FAILURE
        );

        // Slow interval monitors (300s)
        $rulesCreated += $this->createTypeRules(
            $manager,
            $monitorGroups['slow'],
            $channels['webhook'],
            3,
            NotificationType::RECOVERY
        );

        // Flush all pending entities to database
        $manager->flush();

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
     * Get notification channels from database.
     *
     * @return array<string, NotificationChannel> Array of channel type => channel entity
     */
    private function getChannels(EntityManagerInterface $manager): array
    {
        $channels = $manager->getRepository(NotificationChannel::class)->findAll();

        $indexed = [];
        foreach ($channels as $channel) {
            $indexed[$channel->type->value] = $channel;
        }

        return $indexed;
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
        EntityManagerInterface $manager,
        array $monitors,
        NotificationChannel $channel,
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
                $manager,
                $monitor->id->toString(),
                $channel,
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
        EntityManagerInterface $manager,
        array $monitors,
        NotificationChannel $channel,
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
                $manager,
                $monitor->id->toString(),
                $channel,
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
     * @param array<Monitor>                     $monitors
     * @param array<string, NotificationChannel> $channels
     */
    private function createChannelRules(
        EntityManagerInterface $manager,
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
            $channel = $channels[$channelType];

            $this->insertAlertRule(
                $manager,
                $monitor->id->toString(),
                $channel,
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
        EntityManagerInterface $manager,
        array $monitors,
        NotificationChannel $channel,
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
                $manager,
                $monitor->id->toString(),
                $channel,
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
        EntityManagerInterface $manager,
        array $monitors,
        NotificationChannel $channel,
        int $count,
        NotificationType $type
    ): int {
        $created = 0;

        foreach ($monitors as $monitor) {
            if ($created >= $count) {
                break;
            }

            $this->insertAlertRule(
                $manager,
                $monitor->id->toString(),
                $channel,
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
     * Insert a single alert rule using ORM.
     */
    private function insertAlertRule(
        EntityManagerInterface $manager,
        string $monitorId,
        NotificationChannel $channel,
        NotificationType $type,
        int $failureThreshold,
        ?string $cooldownInterval
    ): void {
        $alertRule = AlertRule::create(
            MonitorId::fromString($monitorId),
            $channel,
            $failureThreshold,
            $type,
        );

        if ($cooldownInterval !== null) {
            $alertRule->setCooldownInterval($cooldownInterval);
        }

        $manager->persist($alertRule);
    }
}
