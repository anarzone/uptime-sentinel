<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Notification;

use App\Monitoring\Domain\Model\Alert\AlertChannel;
use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Service\NotificationSenderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Sends notifications via Email using Symfony Mailer.
 */
final readonly class EmailNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromAddress = 'alerts@uptime-sentinel.local',
    ) {
    }

    public function supports(AlertRule $alertRule): bool
    {
        return $alertRule->channel === AlertChannel::EMAIL;
    }

    public function send(AlertRule $alertRule, Monitor $monitor, string $message): void
    {
        if (!$this->supports($alertRule)) {
            return;
        }

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($alertRule->target)
            ->subject(\sprintf('[UptimeSentinel] Alert: %s is DOWN', $monitor->name))
            ->text($message)
            ->html(\sprintf('<h1>🚨 Monitor Alert</h1><p>%s</p>', nl2br(htmlspecialchars($message))));

        try {
            $this->mailer->send($email);
            $this->logger->info('Email notification sent', [
                'monitorId' => $monitor->id->toString(),
                'target' => $alertRule->target,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email notification', [
                'monitorId' => $monitor->id->toString(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
