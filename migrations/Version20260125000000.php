<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;

/**
 * Initial schema setup (Core Domain).
 * Unified migration using Doctrine Schema API for platform independence.
 */
final class Version20260125000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema setup (Core Domain)';
    }

    public function up(Schema $schema): void
    {
        // 1. Monitors
        $monitors = $schema->createTable('monitors');
        $monitors->addColumn('id', Types::STRING, ['length' => 36]);
        $monitors->addColumn('name', Types::STRING, ['length' => 255]);
        $monitors->addColumn('url', Types::STRING, ['length' => 255]);
        $monitors->addColumn('method', Types::STRING, ['length' => 255]);
        $monitors->addColumn('interval_seconds', Types::INTEGER);
        $monitors->addColumn('timeout_seconds', Types::INTEGER);
        $monitors->addColumn('status', Types::STRING, ['length' => 255]);
        $monitors->addColumn('expected_status_code', Types::INTEGER);
        $monitors->addColumn('health_status', Types::STRING, ['length' => 255]);
        $monitors->addColumn('last_status_change_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $monitors->addColumn('consecutive_failures', Types::INTEGER, ['default' => 0]);
        $monitors->addColumn('headers', Types::JSON, ['notnull' => false]);
        $monitors->addColumn('body', Types::TEXT, ['notnull' => false]);
        $monitors->addColumn('last_checked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $monitors->addColumn('next_check_at', Types::DATETIME_IMMUTABLE);
        $monitors->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $monitors->addColumn('updated_at', Types::DATETIME_IMMUTABLE);
        $monitors->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [UnqualifiedName::unquoted('id')], true));

        // 2. Notification Channels
        $channels = $schema->createTable('notification_channels');
        $channels->addColumn('id', Types::STRING, ['length' => 36]);
        $channels->addColumn('name', Types::STRING, ['length' => 255]);
        $channels->addColumn('type', Types::STRING, ['length' => 20]);
        $channels->addColumn('dsn', Types::TEXT);
        $channels->addColumn('is_enabled', Types::BOOLEAN, ['default' => true]);
        $channels->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $channels->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [UnqualifiedName::unquoted('id')], true));

        // 3. Alert Rules
        $rules = $schema->createTable('alert_rules');
        $rules->addColumn('id', Types::STRING, ['length' => 36]);
        $rules->addColumn('monitor_id', Types::STRING, ['length' => 36]);
        $rules->addColumn('notification_channel_id', Types::STRING, ['length' => 36]);
        $rules->addColumn('type', Types::STRING, ['length' => 20]);
        $rules->addColumn('failure_threshold', Types::INTEGER, ['default' => 3]);
        $rules->addColumn('cooldown_interval', Types::STRING, ['length' => 32, 'notnull' => false]);
        $rules->addColumn('is_enabled', Types::BOOLEAN, ['default' => true]);
        $rules->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $rules->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [UnqualifiedName::unquoted('id'), UnqualifiedName::unquoted('monitor_id')], true));
        $rules->addIndex(['monitor_id'], 'idx_alert_rules_monitor');
        $rules->addIndex(['notification_channel_id'], 'idx_alert_rules_channel');
        $rules->addForeignKeyConstraint('monitors', ['monitor_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_alert_rules_monitor');
        $rules->addForeignKeyConstraint('notification_channels', ['notification_channel_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_alert_rules_channel');

        // 4. Escalation Policies
        $policies = $schema->createTable('escalation_policies');
        $policies->addColumn('id', Types::STRING, ['length' => 36]);
        $policies->addColumn('monitor_id', Types::STRING, ['length' => 36]);
        $policies->addColumn('notification_channel_id', Types::STRING, ['length' => 36]);
        $policies->addColumn('level', Types::INTEGER);
        $policies->addColumn('consecutive_failures', Types::INTEGER);
        $policies->addColumn('is_enabled', Types::BOOLEAN, ['default' => true]);
        $policies->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $policies->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [UnqualifiedName::unquoted('id'), UnqualifiedName::unquoted('monitor_id')], true));
        $policies->addIndex(['monitor_id', 'level'], 'idx_escalation_policies_monitor_level');
        $policies->addIndex(['notification_channel_id'], 'idx_escalation_policies_channel');
        $policies->addForeignKeyConstraint('monitors', ['monitor_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_escalation_policies_monitor');
        $policies->addForeignKeyConstraint('notification_channels', ['notification_channel_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_escalation_policies_channel');

        // 5. Notification Templates
        $templates = $schema->createTable('notification_templates');
        $templates->addColumn('id', Types::STRING, ['length' => 36]);
        $templates->addColumn('name', Types::STRING, ['length' => 100]);
        $templates->addColumn('channel', Types::STRING, ['length' => 20]);
        $templates->addColumn('event_type', Types::STRING, ['length' => 20]);
        $templates->addColumn('subject_template', Types::STRING, ['length' => 255, 'notnull' => false]);
        $templates->addColumn('body_template', Types::TEXT);
        $templates->addColumn('is_default', Types::BOOLEAN, ['default' => false]);
        $templates->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $templates->addColumn('updated_at', Types::DATETIME_IMMUTABLE);
        $templates->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [UnqualifiedName::unquoted('id')], true));
        $templates->addUniqueIndex(['name'], 'unique_template_name');
        $templates->addUniqueIndex(['channel', 'event_type', 'is_default'], 'unique_template_channel_event');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('notification_templates');
        $schema->dropTable('escalation_policies');
        $schema->dropTable('alert_rules');
        $schema->dropTable('notification_channels');
        $schema->dropTable('monitors');
    }
}
