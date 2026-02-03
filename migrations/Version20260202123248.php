<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202123248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ping_results');
        $this->addSql('DROP TABLE ping_stats_daily');
        $this->addSql('DROP TABLE ping_stats_hourly');
        $this->addSql('ALTER TABLE alert_rules DROP FOREIGN KEY `FK_alert_rules_monitor`');
        $this->addSql('DROP INDEX idx_alert_rules_monitor ON alert_rules');
        $this->addSql('ALTER TABLE alert_rules RENAME INDEX idx_alert_rules_channel TO IDX_63C06ED789870488');
        $this->addSql('ALTER TABLE escalation_policies DROP FOREIGN KEY `FK_escalation_policies_monitor`');
        $this->addSql('DROP INDEX IDX_E659FDB14CE1C902 ON escalation_policies');
        $this->addSql('ALTER TABLE escalation_policies RENAME INDEX idx_escalation_policies_channel TO IDX_E659FDB189870488');
        $this->addSql('ALTER TABLE notification_templates RENAME INDEX unique_template_name TO unique_name');
        $this->addSql('ALTER TABLE notification_templates RENAME INDEX unique_template_channel_event TO unique_channel_event');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ping_results (id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, monitor_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, status_code SMALLINT NOT NULL, latency_ms INT NOT NULL, is_successful TINYINT NOT NULL, created_at DATETIME NOT NULL, INDEX idx_monitor_created (monitor_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ping_stats_daily (monitor_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, bucket_time DATE NOT NULL COMMENT \'The day\', ping_count INT UNSIGNED DEFAULT 0 NOT NULL, success_count INT UNSIGNED DEFAULT 0 NOT NULL, avg_latency_ms INT UNSIGNED DEFAULT 0 NOT NULL, max_latency_ms INT UNSIGNED DEFAULT 0 NOT NULL, INDEX idx_bucket_time (bucket_time), PRIMARY KEY (monitor_id, bucket_time)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ping_stats_hourly (monitor_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, bucket_time DATETIME NOT NULL COMMENT \'Start of the hour\', ping_count INT UNSIGNED DEFAULT 0 NOT NULL, success_count INT UNSIGNED DEFAULT 0 NOT NULL, avg_latency_ms INT UNSIGNED DEFAULT 0 NOT NULL, max_latency_ms INT UNSIGNED DEFAULT 0 NOT NULL, INDEX idx_bucket_time (bucket_time), PRIMARY KEY (monitor_id, bucket_time)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT `FK_alert_rules_monitor` FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_alert_rules_monitor ON alert_rules (monitor_id)');
        $this->addSql('ALTER TABLE alert_rules RENAME INDEX idx_63c06ed789870488 TO idx_alert_rules_channel');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT `FK_escalation_policies_monitor` FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E659FDB14CE1C902 ON escalation_policies (monitor_id)');
        $this->addSql('ALTER TABLE escalation_policies RENAME INDEX idx_e659fdb189870488 TO idx_escalation_policies_channel');
        $this->addSql('ALTER TABLE notification_templates RENAME INDEX unique_channel_event TO unique_template_channel_event');
        $this->addSql('ALTER TABLE notification_templates RENAME INDEX unique_name TO unique_template_name');
    }
}
