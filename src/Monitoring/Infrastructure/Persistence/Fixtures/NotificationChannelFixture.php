<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Persistence\Fixtures;

use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NotificationChannelFixture extends Fixture
{
    public const CHANNEL_EMAIL_REFERENCE = 'channel-email';
    public const CHANNEL_SLACK_REFERENCE = 'channel-slack';
    public const CHANNEL_WEBHOOK_REFERENCE = 'channel-webhook';

    public function load(ObjectManager $manager): void
    {
        $emailChannel = NotificationChannel::create(
            'System Administrators',
            NotificationChannelType::EMAIL,
            'admin@uptime-sentinel.local' // DSN for email is the target address
        );

        $manager->persist($emailChannel);
        $this->addReference(self::CHANNEL_EMAIL_REFERENCE, $emailChannel);

        $slackChannel = NotificationChannel::create(
            'DevOps Slack',
            NotificationChannelType::SLACK,
            'slack://token@default?channel=alerts' // Dummy DSN
        );
        $manager->persist($slackChannel);
        $this->addReference(self::CHANNEL_SLACK_REFERENCE, $slackChannel);

        $webhookChannel = NotificationChannel::create(
            'Incident Webhook',
            NotificationChannelType::WEBHOOK,
            'https://webhook.site/uptime-sentinel-alerts' // Example webhook endpoint
        );
        $manager->persist($webhookChannel);
        $this->addReference(self::CHANNEL_WEBHOOK_REFERENCE, $webhookChannel);

        $manager->flush();
    }
}
