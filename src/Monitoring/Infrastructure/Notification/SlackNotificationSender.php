<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Notification;

use App\Monitoring\Domain\Model\Alert\AlertChannel;
use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Service\NotificationSenderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends notifications via Slack Incoming Webhooks.
 */
final readonly class SlackNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(AlertRule $alertRule): bool
    {
        return $alertRule->channel === AlertChannel::SLACK;
    }

    public function send(AlertRule $alertRule, Monitor $monitor, string $message): void
    {
        if (!$this->supports($alertRule)) {
            return;
        }

        $payload = [
            'text' => \sprintf("🚨 *%s is DOWN*\n%s", $monitor->name, $message),
            'username' => 'UptimeSentinel',
            'icon_emoji' => ':rotating_light:',
        ];

        try {
            $this->httpClient->request('POST', $alertRule->target, [
                'json' => $payload,
            ]);
            $this->logger->info('Slack notification sent', [
                'monitorId' => $monitor->id->toString(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send Slack notification', [
                'monitorId' => $monitor->id->toString(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
