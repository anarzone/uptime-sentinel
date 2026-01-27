<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema setup squashed for better maintainability.
 * Includes monitors, alert rules, escalation policies, and notification templates.
 */
final class Version20260125000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema setup';
    }

    public function up(Schema $schema): void
    {
        // Monitors table
        $this->addSql('CREATE TABLE monitors (
            name VARCHAR(255) NOT NULL, 
            method VARCHAR(255) NOT NULL, 
            interval_seconds INT NOT NULL, 
            timeout_seconds INT NOT NULL, 
            status VARCHAR(255) NOT NULL, 
            expected_status_code INT NOT NULL, 
            health_status VARCHAR(255) NOT NULL, 
            last_status_change_at DATETIME DEFAULT NULL, 
            consecutive_failures INT DEFAULT 0 NOT NULL, 
            headers JSON DEFAULT NULL, 
            body LONGTEXT DEFAULT NULL, 
            last_checked_at DATETIME DEFAULT NULL, 
            next_check_at DATETIME NOT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME NOT NULL, 
            id_value VARCHAR(36) NOT NULL, 
            url_value VARCHAR(255) NOT NULL, 
            PRIMARY KEY(id_value)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

        // Alert Rules table
        $this->addSql('CREATE TABLE alert_rules (
            channel VARCHAR(20) NOT NULL, 
            type VARCHAR(20) NOT NULL, 
            target VARCHAR(255) NOT NULL, 
            failure_threshold INT DEFAULT 3 NOT NULL, 
            cooldown_interval VARCHAR(32) DEFAULT NULL, 
            is_enabled TINYINT DEFAULT 1 NOT NULL, 
            created_at DATETIME NOT NULL, 
            id_value VARCHAR(36) NOT NULL, 
            monitor_id_value VARCHAR(36) NOT NULL, 
            PRIMARY KEY(id_value, monitor_id_value)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

        // Escalation Policies table
        $this->addSql('CREATE TABLE escalation_policies (
            level INT NOT NULL, 
            consecutive_failures INT NOT NULL, 
            channel VARCHAR(20) NOT NULL, 
            target VARCHAR(255) NOT NULL, 
            is_enabled TINYINT DEFAULT 1 NOT NULL, 
            created_at DATETIME NOT NULL, 
            id_value VARCHAR(36) NOT NULL, 
            monitor_id_value VARCHAR(36) NOT NULL, 
            INDEX idx_monitor_level (monitor_id_value, level), 
            PRIMARY KEY(id_value, monitor_id_value)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

        // Notification Templates table
        $this->addSql('CREATE TABLE notification_templates (
            id VARCHAR(36) NOT NULL, 
            name VARCHAR(100) NOT NULL, 
            channel VARCHAR(20) NOT NULL, 
            event_type VARCHAR(20) NOT NULL, 
            subject_template VARCHAR(255) DEFAULT NULL, 
            body_template LONGTEXT NOT NULL, 
            is_default TINYINT DEFAULT 0 NOT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME NOT NULL, 
            UNIQUE INDEX unique_name (name), 
            UNIQUE INDEX unique_channel_event (channel, event_type, is_default), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS notification_templates');
        $this->addSql('DROP TABLE IF EXISTS escalation_policies');
        $this->addSql('DROP TABLE IF EXISTS alert_rules');
        $this->addSql('DROP TABLE IF EXISTS monitors');
    }
}
