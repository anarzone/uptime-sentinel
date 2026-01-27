<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Establish clean naming convention and add missing constraints.
 *
 * This migration:
 * - Renames all primary keys from 'uuid' to 'id' for cleaner naming
 * - Renames foreign keys from '{entity}_id_uuid' to '{entity}_id'
 * - Renames value object column from 'url_value' to 'url'
 * - Adds missing foreign key constraints for referential integrity
 * - Adds indexes for query optimization
 */
final class Version20260127000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Establish clean naming convention and add missing constraints';
    }

    public function up(Schema $schema): void
    {
        // 1. monitors table
        $this->addSql('ALTER TABLE monitors CHANGE uuid id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE monitors CHANGE url_value url VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE monitors DROP PRIMARY KEY, ADD PRIMARY KEY (id)');

        // 2. notification_channels table
        $this->addSql('ALTER TABLE notification_channels CHANGE uuid id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE notification_channels DROP PRIMARY KEY, ADD PRIMARY KEY (id)');

        // 3. alert_rules table
        $this->addSql('ALTER TABLE alert_rules CHANGE uuid id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE alert_rules CHANGE monitor_id_uuid monitor_id VARCHAR(36) NOT NULL');

        // Drop old foreign key
        $this->addSql('ALTER TABLE alert_rules DROP FOREIGN KEY FK_63C06ED789870488');

        // Update primary key
        $this->addSql('ALTER TABLE alert_rules DROP PRIMARY KEY, ADD PRIMARY KEY (id, monitor_id)');

        // Add foreign keys with new naming
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT FK_alert_rules_monitor FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT FK_alert_rules_notification_channel FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id) ON DELETE CASCADE');

        // Add index for monitor lookups
        $this->addSql('CREATE INDEX idx_alert_rules_monitor ON alert_rules (monitor_id)');

        // 4. escalation_policies table
        $this->addSql('ALTER TABLE escalation_policies CHANGE uuid id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE escalation_policies CHANGE monitor_id_uuid monitor_id VARCHAR(36) NOT NULL');

        // Drop old foreign key and index
        $this->addSql('ALTER TABLE escalation_policies DROP FOREIGN KEY FK_E659FDB189870488');
        $this->addSql('DROP INDEX idx_monitor_level ON escalation_policies');

        // Update primary key
        $this->addSql('ALTER TABLE escalation_policies DROP PRIMARY KEY, ADD PRIMARY KEY (id, monitor_id)');

        // Add foreign keys with new naming
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT FK_escalation_policies_monitor FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT FK_escalation_policies_notification_channel FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id) ON DELETE CASCADE');

        // Re-create index with new names
        $this->addSql('CREATE INDEX idx_escalation_policies_monitor_level ON escalation_policies (monitor_id, level)');
    }

    public function down(Schema $schema): void
    {
        // Rollback escalation_policies
        $this->addSql('DROP INDEX idx_escalation_policies_monitor_level ON escalation_policies');
        $this->addSql('ALTER TABLE escalation_policies DROP FOREIGN KEY FK_escalation_policies_notification_channel');
        $this->addSql('ALTER TABLE escalation_policies DROP FOREIGN KEY FK_escalation_policies_monitor');
        $this->addSql('ALTER TABLE escalation_policies DROP PRIMARY KEY, ADD PRIMARY KEY (id, monitor_id)');
        $this->addSql('ALTER TABLE escalation_policies CHANGE monitor_id monitor_id_uuid VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE escalation_policies CHANGE id uuid VARCHAR(36) NOT NULL');
        $this->addSql('CREATE INDEX idx_monitor_level ON escalation_policies (monitor_id_uuid, level)');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT FK_E659FDB189870488 FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        // Rollback alert_rules
        $this->addSql('DROP INDEX idx_alert_rules_monitor ON alert_rules');
        $this->addSql('ALTER TABLE alert_rules DROP FOREIGN KEY FK_alert_rules_notification_channel');
        $this->addSql('ALTER TABLE alert_rules DROP FOREIGN KEY FK_alert_rules_monitor');
        $this->addSql('ALTER TABLE alert_rules DROP PRIMARY KEY, ADD PRIMARY KEY (id, monitor_id)');
        $this->addSql('ALTER TABLE alert_rules CHANGE monitor_id monitor_id_uuid VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE alert_rules CHANGE id uuid VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT FK_63C06ED789870488 FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        // Rollback notification_channels
        $this->addSql('ALTER TABLE notification_channels DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE notification_channels CHANGE id uuid VARCHAR(36) NOT NULL');

        // Rollback monitors
        $this->addSql('ALTER TABLE monitors DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE monitors CHANGE url url_value VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE monitors CHANGE id uuid VARCHAR(36) NOT NULL');
    }
}
