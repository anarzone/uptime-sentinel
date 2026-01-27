<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Service;

use App\Monitoring\Domain\Model\Alert\AlertChannel;
use App\Monitoring\Domain\Model\Alert\NotificationType;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorHealth;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorStateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Transport;
use Symfony\Component\RateLimiter\Policy\FixedWindowLimiter;
use Symfony\Component\RateLimiter\Storage\StorageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Checks if a monitor has reached the failure threshold for any alert rule
 * and dispatches notifications via Symfony Notifier (Chat) or Mailer (Email).
 */
final readonly class AlertNotificationService
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private EscalationPolicyRepositoryInterface $escalationPolicyRepository,
        private MonitorStateRepositoryInterface $monitorStateRepository,
        private StorageInterface $rateLimiterStorage,
        private HttpClientInterface $httpClient,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $mailerFrom,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {
    }

    /**
     * Check if the monitor should trigger any alerts and send notifications.
     */
    public function checkAndNotify(Monitor $monitor): void
    {
        $previousHealth = $this->monitorStateRepository->getLastHealthStatus($monitor->id);
        $currentHealth = $monitor->healthStatus;

        $this->logger->info('AlertNotificationService called', [
            'monitor' => $monitor->name,
            'health' => $currentHealth->value,
            'failures' => $monitor->consecutiveFailures,
        ]);

        // Handle failure notifications
        if ($currentHealth === MonitorHealth::DOWN) {
            $this->handleFailureNotifications($monitor);
            $this->handleEscalationNotifications($monitor);
        }

        // Handle recovery notifications (transition from DOWN to UP)
        if ($previousHealth === MonitorHealth::DOWN && $currentHealth === MonitorHealth::UP) {
            $this->handleRecoveryNotifications($monitor);
        }

        // Save current state for next time
        $this->monitorStateRepository->saveHealthStatus($monitor->id, $currentHealth);
    }

    private function handleFailureNotifications(Monitor $monitor): void
    {
        $this->handleAlertRules($monitor, NotificationType::FAILURE);
    }

    private function handleRecoveryNotifications(Monitor $monitor): void
    {
        $this->handleAlertRules($monitor, NotificationType::RECOVERY);
    }

    private function handleAlertRules(Monitor $monitor, NotificationType $type): void
    {
        $alertRules = $this->alertRuleRepository->findEnabledByMonitorId($monitor->id);

        $this->logger->info(\sprintf(
            'Processing alert rules: count=%d, type=%s, monitor=%s, failures=%d',
            \count($alertRules),
            $type->value,
            $monitor->name,
            $monitor->consecutiveFailures
        ));

        foreach ($alertRules as $rule) {
            $this->logger->info(\sprintf(
                'Checking rule: id=%s, type=%s, threshold=%d',
                $rule->id->toString(),
                $rule->type->value,
                $rule->failureThreshold
            ));

            if ($rule->type !== $type && $rule->type !== NotificationType::BOTH) {
                $this->logger->info('Skipping - type mismatch');
                continue;
            }

            if ($type === NotificationType::FAILURE && $monitor->consecutiveFailures < $rule->failureThreshold) {
                $this->logger->info('Skipping - threshold not met');
                continue;
            }

            // Rate Limit
            $interval = $rule->getCooldownInterval() ?? new \DateInterval('PT1H');
            $limitKey = 'notification:rule:'.$monitor->id->toString().':'.$rule->id->toString();

            if ($this->isRateLimited($limitKey, $interval)) {
                $this->logger->info('Skipping - rate limited: '.$limitKey);
                continue;
            }

            $this->logger->info('Dispatching notification!', ['ruleId' => $rule->id->toString()]);

            if ($type === NotificationType::FAILURE) {
                $subject = \sprintf("Monitor '%s' is DOWN", $monitor->name);
                $message = \sprintf(
                    "Monitor '%s' (%s) has been DOWN for %d consecutive checks.\nLast checked at: %s",
                    $monitor->name,
                    $monitor->url->toString(),
                    $monitor->consecutiveFailures,
                    $monitor->lastCheckedAt?->format('Y-m-d H:i:s') ?? 'N/A'
                );
            } else {
                $subject = \sprintf("Monitor '%s' Recovered", $monitor->name);
                $message = \sprintf(
                    "Monitor '%s' (%s) has recovered!\nIt is now UP as of %s.",
                    $monitor->name,
                    $monitor->url->toString(),
                    $monitor->lastCheckedAt?->format('Y-m-d H:i:s') ?? 'N/A'
                );
            }

            $this->dispatchNotification(
                $rule->notificationChannel,
                $subject,
                $message,
                $monitor,
                'rule-'.$rule->id->toString()
            );
        }
    }

    private function handleEscalationNotifications(Monitor $monitor): void
    {
        $policies = $this->escalationPolicyRepository->findApplicableForMonitor($monitor->id);

        foreach ($policies as $policy) {
            if (!$policy->shouldTrigger($monitor)) {
                continue;
            }

            // Rate Limit
            $limitKey = 'notification:escalation:'.$monitor->id->toString().':'.$policy->id->toString();

            if ($this->isRateLimited($limitKey, new \DateInterval('PT1H'))) {
                continue;
            }

            $subject = \sprintf("ESCALATION (Level %d): Monitor '%s' DOWN", $policy->level, $monitor->name);
            $message = \sprintf(
                "ESCALATION (Level %d): Monitor '%s' (%s) has been DOWN for %d consecutive checks.",
                $policy->level,
                $monitor->name,
                $monitor->url->toString(),
                $monitor->consecutiveFailures
            );

            $this->dispatchNotification(
                $policy->notificationChannel,
                $subject,
                $message,
                $monitor,
                'escalation-'.$policy->id->toString()
            );
        }
    }

    private function isRateLimited(string $key, \DateInterval $interval): bool
    {
        $limiter = new FixedWindowLimiter($key, 1, $interval, $this->rateLimiterStorage);
        $result = $limiter->consume();

        $this->logger->info("Rate limiter: key=$key, accepted=".($result->isAccepted() ? 'true' : 'false').', tokens='.$result->getRemainingTokens());

        return $result->isAccepted() === false;
    }

    private function dispatchNotification(
        NotificationChannel $channel,
        string $subject,
        string $body,
        Monitor $monitor,
        string $contextId
    ): void {
        try {
            if ($channel->type === AlertChannel::EMAIL) {
                $email = new Email()
                    ->from($this->mailerFrom)
                    ->to($channel->dsn)
                    ->subject($subject)
                    ->text($body);

                $this->mailer->send($email);
            } else {
                $transport = Transport::fromDsn($channel->dsn, $this->eventDispatcher, $this->httpClient);

                $chatMessage = new ChatMessage($subject."\n\n".$body);
                $transport->send($chatMessage);
            }

            $this->logger->info('Notification dispatched', [
                'type' => $channel->type->value,
                'channel' => $channel->name,
                'monitor' => $monitor->name,
                'context' => $contextId,
            ]);
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Log mailer errors but don't re-throw in message handler context
            // Messenger will retry for transient failures, but will permanently fail
            // for configuration errors (e.g. invalid DSN)
            $this->logger->error('Failed to dispatch email notification', [
                'type' => $channel->type->value,
                'channel' => $channel->name,
                'error' => $e->getMessage(),
                'monitor' => $monitor->id->toString(),
            ]);
        } catch (\Symfony\Component\Notifier\Exception\TransportException $e) {
            // Log notifier errors but don't re-throw in message handler context
            $this->logger->error('Failed to dispatch chat notification', [
                'type' => $channel->type->value,
                'channel' => $channel->name,
                'error' => $e->getMessage(),
                'monitor' => $monitor->id->toString(),
            ]);
        }
    }
}
