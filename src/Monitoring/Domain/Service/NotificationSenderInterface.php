<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Service;

use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Model\Monitor\Monitor;

/**
 * Interface for sending notifications through various channels (Email, Slack, Webhook).
 */
interface NotificationSenderInterface
{
    /**
     * Send a notification based on the alert rule.
     *
     * @param AlertRule $alertRule The rule defining how/where to send
     * @param Monitor   $monitor   The monitor that triggered the alert
     * @param string    $message   The notification message
     */
    public function send(AlertRule $alertRule, Monitor $monitor, string $message): void;

    /**
     * Check if this sender supports the given channel.
     */
    public function supports(AlertRule $alertRule): bool;
}
