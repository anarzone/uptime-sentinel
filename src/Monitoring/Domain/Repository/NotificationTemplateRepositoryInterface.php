<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Repository;

use App\Monitoring\Domain\Model\Alert\NotificationEventType;
use App\Monitoring\Domain\Model\Alert\NotificationTemplate;
use App\Monitoring\Domain\Model\Notification\NotificationChannelType;

/**
 * Repository for managing notification templates.
 */
interface NotificationTemplateRepositoryInterface
{
    /**
     * Find the default template for a specific channel and event type.
     */
    public function findDefault(NotificationChannelType $channel, NotificationEventType $eventType): ?NotificationTemplate;

    /**
     * Find a template by its ID.
     */
    public function findById(string $id): ?NotificationTemplate;

    /**
     * Find all templates, optionally filtered by channel and/or event type.
     *
     * @return array<NotificationTemplate>
     */
    public function findAll(?NotificationChannelType $channel = null, ?NotificationEventType $eventType = null): array;

    /**
     * Save a template (create or update).
     */
    public function save(NotificationTemplate $template): void;

    /**
     * Delete a template.
     */
    public function remove(NotificationTemplate $template): void;

    /**
     * Check if a template exists.
     */
    public function exists(string $id): bool;
}
