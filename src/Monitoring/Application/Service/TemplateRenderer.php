<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Service;

use App\Monitoring\Domain\Model\Alert\NotificationEventType;
use App\Monitoring\Domain\Model\Alert\NotificationRendered;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use App\Monitoring\Domain\Repository\NotificationTemplateRepositoryInterface;

final readonly class TemplateRenderer
{
    public function __construct(
        private NotificationTemplateRepositoryInterface $templateRepository,
    ) {
    }

    public function renderForMonitor(
        Monitor $monitor,
        NotificationEventType $eventType,
        NotificationChannelType $channel,
        ?string $fallbackMessage = null,
    ): NotificationRendered {
        $template = $this->templateRepository->findDefault($channel, $eventType);

        if ($template === null) {
            return $this->getFallbackMessage($monitor, $eventType, $fallbackMessage);
        }

        $variables = $this->extractVariables($monitor, $eventType);

        return $template->render($variables);
    }

    private function extractVariables(Monitor $monitor, NotificationEventType $eventType): array
    {
        return [
            'monitorId' => $monitor->id->toString(),
            'monitorName' => $monitor->name,
            'url' => $monitor->url->toString(),
            'method' => $monitor->method->value,
            'healthStatus' => $monitor->healthStatus->value,
            'consecutiveFailures' => $monitor->consecutiveFailures,
            'lastCheckedAt' => $monitor->lastCheckedAt?->format('Y-m-d H:i:s') ?? 'N/A',
            'lastStatusChangeAt' => $monitor->lastStatusChangeAt?->format('Y-m-d H:i:s') ?? 'N/A',
            'expectedStatusCode' => $monitor->expectedStatusCode,
            'eventType' => $eventType->value,
            'timestamp' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];
    }

    private function getFallbackMessage(
        Monitor $monitor,
        NotificationEventType $eventType,
        ?string $customMessage,
    ): NotificationRendered {
        if ($customMessage !== null) {
            return new NotificationRendered(null, $customMessage);
        }

        $message = match ($eventType) {
            NotificationEventType::FAILURE => \sprintf(
                "Monitor '%s' (%s) is DOWN\n\nConsecutive failures: %d\nLast checked: %s",
                $monitor->name,
                $monitor->url->toString(),
                $monitor->consecutiveFailures,
                $monitor->lastCheckedAt?->format('Y-m-d H:i:s') ?? 'N/A'
            ),
            NotificationEventType::RECOVERY => \sprintf(
                "Monitor '%s' (%s) has recovered!\n\nIt is now UP as of %s",
                $monitor->name,
                $monitor->url->toString(),
                $monitor->lastCheckedAt?->format('Y-m-d H:i:s') ?? 'N/A'
            ),
            NotificationEventType::ESCALATION => \sprintf(
                "ESCALATION: Monitor '%s' (%s) continues to fail\n\nConsecutive failures: %d",
                $monitor->name,
                $monitor->url->toString(),
                $monitor->consecutiveFailures
            ),
        };

        return new NotificationRendered(null, $message);
    }
}
