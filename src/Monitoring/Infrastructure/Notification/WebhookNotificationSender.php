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
 * Sends notifications via generic Webhooks (POST request with JSON body).
 */
final readonly class WebhookNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(AlertRule $alertRule): bool
    {
        return $alertRule->channel === AlertChannel::WEBHOOK;
    }

    public function send(AlertRule $alertRule, Monitor $monitor, string $message): void
    {
        if (!$this->supports($alertRule)) {
            return;
        }

        $payload = [
            'event' => 'monitor.down',
            'monitor_id' => $monitor->id->toString(),
            'monitor_name' => $monitor->name,
            'url' => $monitor->url->toString(),
            'message' => $message,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        try {
            $this->httpClient->request('POST', $alertRule->target, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'UptimeSentinel/1.0',
                ],
            ]);
            $this->logger->info('Webhook notification sent', [
                'monitorId' => $monitor->id->toString(),
                'target' => $alertRule->target,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send Webhook notification', [
                'monitorId' => $monitor->id->toString(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
