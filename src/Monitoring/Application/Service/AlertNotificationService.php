<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Service;

use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorHealth;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Service\NotificationSenderInterface;
use Psr\Log\LoggerInterface;

/**
 * Checks if a monitor has reached the failure threshold for any alert rule
 * and dispatches notifications if necessary.
 */
final readonly class AlertNotificationService
{
    /**
     * @param iterable<NotificationSenderInterface> $senders
     */
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private iterable $senders,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Check if the monitor should trigger any alerts and send notifications.
     */
    public function checkAndNotify(Monitor $monitor): void
    {
        // Only send alerts for DOWN status
        if ($monitor->healthStatus !== MonitorHealth::DOWN) {
            return;
        }

        // Find all enabled alert rules for this monitor
        $alertRules = $this->alertRuleRepository->findEnabledByMonitorId($monitor->id);

        foreach ($alertRules as $rule) {
            // Check if the failure threshold has been reached
            if ($monitor->consecutiveFailures < $rule->failureThreshold) {
                continue;
            }

            // Only send alert on the exact threshold (not every time after)
            if ($monitor->consecutiveFailures !== $rule->failureThreshold) {
                continue;
            }

            $message = \sprintf(
                "Monitor '%s' (%s) has been DOWN for %d consecutive checks.\n\nLast checked at: %s",
                $monitor->name,
                $monitor->url->toString(),
                $monitor->consecutiveFailures,
                $monitor->lastCheckedAt?->format('Y-m-d H:i:s') ?? 'N/A'
            );

            // Find the appropriate sender for this rule
            foreach ($this->senders as $sender) {
                if ($sender->supports($rule)) {
                    $sender->send($rule, $monitor, $message);
                    $this->logger->info('Alert notification dispatched', [
                        'monitorId' => $monitor->id->toString(),
                        'channel' => $rule->channel->value,
                        'target' => $rule->target,
                    ]);
                    break;
                }
            }
        }
    }
}
