<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Symfony\Component\Uid\UuidV7;

/**
 * Consolidated Initial Schema Migration
 */
final class Version20260205200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Consolidated Initial Schema';
    }

    public function up(Schema $schema): void
    {
        // 1. Users
        $this->addSql('CREATE TABLE `users` (id VARCHAR(36) NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', is_registered TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. Monitors
        $this->addSql('CREATE TABLE monitors (name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, method VARCHAR(255) NOT NULL, interval_seconds INT NOT NULL, timeout_seconds INT NOT NULL, status VARCHAR(255) NOT NULL, expected_status_code INT NOT NULL, headers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', body LONGTEXT DEFAULT NULL, last_checked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', next_check_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', health_status VARCHAR(255) NOT NULL, last_status_change_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', consecutive_failures INT DEFAULT 0 NOT NULL, owner_id VARCHAR(36) DEFAULT NULL, id VARCHAR(36) NOT NULL, INDEX idx_monitor_owner (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. Notification Channels
        $this->addSql('CREATE TABLE notification_channels (name VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, dsn LONGTEXT NOT NULL, is_enabled TINYINT(1) DEFAULT 1 NOT NULL, owner_id VARCHAR(36) DEFAULT NULL, created_at DATETIME NOT NULL, id VARCHAR(36) NOT NULL, INDEX idx_notification_channel_owner (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. Alert Rules
        $this->addSql('CREATE TABLE alert_rules (monitor_id VARCHAR(36) NOT NULL, target VARCHAR(255) NOT NULL, failure_threshold INT NOT NULL, type VARCHAR(20) NOT NULL, is_enabled TINYINT(1) DEFAULT 1 NOT NULL, last_triggered_at DATETIME DEFAULT NULL, cooldown_interval INT DEFAULT 3600 NOT NULL, notification_channel_id VARCHAR(36) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id VARCHAR(36) NOT NULL, INDEX IDX_63C06ED74CEAA4F1 (monitor_id), INDEX IDX_63C06ED789870488 (notification_channel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 5. Escalation Policies
        $this->addSql('CREATE TABLE escalation_policies (monitor_id VARCHAR(36) NOT NULL, notification_channel_id VARCHAR(36) NOT NULL, level INT NOT NULL, consecutive_failures INT NOT NULL, is_enabled TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, id VARCHAR(36) NOT NULL, INDEX idx_escalation_policies_monitor_level (monitor_id, level), INDEX idx_escalation_policies_channel (notification_channel_id), PRIMARY KEY(id, monitor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 6. Notification Templates
        $this->addSql('CREATE TABLE notification_templates (id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, channel VARCHAR(20) NOT NULL, event_type VARCHAR(20) NOT NULL, subject_template VARCHAR(255) DEFAULT NULL, body_template LONGTEXT NOT NULL, is_default TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX unique_template_name (name), UNIQUE INDEX unique_template_channel_event (channel, event_type, is_default), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 7. Ping Results (High Throughput - With Partitioning for MySQL)
        $this->addSql('CREATE TABLE ping_results (id CHAR(36) NOT NULL, monitor_id CHAR(36) NOT NULL, status_code SMALLINT NOT NULL, latency_ms INT NOT NULL, is_successful TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id, created_at)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE INDEX idx_monitor_created ON ping_results (monitor_id, created_at)');

        if ($this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
            $this->addSql("ALTER TABLE ping_results PARTITION BY RANGE COLUMNS(created_at) (
                PARTITION p_initial VALUES LESS THAN ('2026-01-01'),
                PARTITION p_future VALUES LESS THAN (MAXVALUE)
            )");
        }

        // 8. Telemetry Aggregates
        $this->addSql('CREATE TABLE ping_stats_hourly (monitor_id CHAR(36) NOT NULL, bucket_time DATETIME NOT NULL, ping_count INT UNSIGNED NOT NULL DEFAULT 0, success_count INT UNSIGNED NOT NULL DEFAULT 0, avg_latency_ms INT UNSIGNED NOT NULL DEFAULT 0, max_latency_ms INT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (monitor_id, bucket_time)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE INDEX idx_bucket_time_hourly ON ping_stats_hourly (bucket_time)');

        $this->addSql('CREATE TABLE ping_stats_daily (monitor_id CHAR(36) NOT NULL, bucket_time DATE NOT NULL, ping_count INT UNSIGNED NOT NULL DEFAULT 0, success_count INT UNSIGNED NOT NULL DEFAULT 0, avg_latency_ms INT UNSIGNED NOT NULL DEFAULT 0, max_latency_ms INT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (monitor_id, bucket_time)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE INDEX idx_bucket_time_daily ON ping_stats_daily (bucket_time)');

        // Foreign Key Constraints
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT FK_63C06ED74CEAA4F1 FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT FK_63C06ED789870488 FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT FK_escalation_policies_monitor FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT FK_escalation_policies_channel FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id) ON DELETE CASCADE');

        // 9. Seed Notification Templates
        $templates = [
            ['name' => 'Default Email Failure', 'channel' => 'email', 'event_type' => 'failure', 'subject' => '🚨 Alert: {{monitorName}} is DOWN', 'body' => '<h1>🚨 Monitor Alert</h1><p><strong>Monitor:</strong> {{monitorName}}</p><p><strong>URL:</strong> {{url}}</p><p><strong>Status:</strong> {{healthStatus}}</p><p><strong>Consecutive Failures:</strong> {{consecutiveFailures}}</p><p><strong>Last Checked:</strong> {{lastCheckedAt}}</p><hr><p><em>This is an automated message from UptimeSentinel</em></p>'],
            ['name' => 'Default Email Recovery', 'channel' => 'email', 'event_type' => 'recovery', 'subject' => '✅ Recovery: {{monitorName}} is UP', 'body' => '<h1>✅ Monitor Recovered</h1><p><strong>Monitor:</strong> {{monitorName}}</p><p><strong>URL:</strong> {{url}}</p><p><strong>Status:</strong> The monitor has recovered and is now UP</p><p><strong>Recovered At:</strong> {{lastCheckedAt}}</p><hr><p><em>This is an automated message from UptimeSentinel</em></p>'],
            ['name' => 'Default Slack Failure', 'channel' => 'slack', 'event_type' => 'failure', 'subject' => null, 'body' => ':x: *{{monitorName}} is DOWN* • URL: {{url}} • Status: {{healthStatus}} • Failures: {{consecutiveFailures}} • Last Checked: {{lastCheckedAt}}'],
            ['name' => 'Default Slack Recovery', 'channel' => 'slack', 'event_type' => 'recovery', 'subject' => null, 'body' => ':white_check_mark: *{{monitorName}} has recovered* • URL: {{url}} • The monitor is now UP • Recovered at: {{lastCheckedAt}}'],
            ['name' => 'Default Slack Escalation', 'channel' => 'slack', 'event_type' => 'escalation', 'subject' => null, 'body' => ':rotating_light: *ESCALATION: {{monitorName}}* • URL: {{url}} • Consecutive failures: {{consecutiveFailures}} • Last checked: {{lastCheckedAt}}'],
            ['name' => 'Default Webhook Failure', 'channel' => 'webhook', 'event_type' => 'failure', 'subject' => null, 'body' => 'Monitor {{monitorName}} is DOWN ({{url}}). Consecutive failures: {{consecutiveFailures}}'],
        ];

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        foreach ($templates as $tpl) {
            $this->addSql(
                'INSERT INTO notification_templates (id, name, channel, event_type, subject_template, body_template, is_default, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    (new UuidV7())->toRfc4122(),
                    $tpl['name'],
                    $tpl['channel'],
                    $tpl['event_type'],
                    $tpl['subject'],
                    $tpl['body'],
                    1,
                    $now,
                    $now,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ping_stats_daily');
        $this->addSql('DROP TABLE ping_stats_hourly');
        $this->addSql('DROP TABLE ping_results');
        $this->addSql('DROP TABLE notification_templates');
        $this->addSql('DROP TABLE escalation_policies');
        $this->addSql('DROP TABLE alert_rules');
        $this->addSql('DROP TABLE notification_channels');
        $this->addSql('DROP TABLE monitors');
        $this->addSql('DROP TABLE `users`');
    }
}
