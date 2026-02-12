<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211210233 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Fix schema drift and normalize index names';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert_rules DROP FOREIGN KEY `FK_63C06ED789870488`');
        $this->addSql('ALTER TABLE alert_rules DROP target, DROP last_triggered_at, DROP updated_at, CHANGE failure_threshold failure_threshold INT DEFAULT 3 NOT NULL, CHANGE cooldown_interval cooldown_interval VARCHAR(32) DEFAULT NULL, CHANGE notification_channel_id notification_channel_id VARCHAR(36) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id, monitor_id)');

        // Convert old integer values (seconds) to ISO 8601 duration strings
        $this->addSql("UPDATE alert_rules SET cooldown_interval = 'PT0S' WHERE cooldown_interval = '0'");
        $this->addSql("UPDATE alert_rules SET cooldown_interval = 'PT5M' WHERE cooldown_interval = '300'");
        $this->addSql("UPDATE alert_rules SET cooldown_interval = 'PT30M' WHERE cooldown_interval = '1800'");
        $this->addSql("UPDATE alert_rules SET cooldown_interval = 'PT1H' WHERE cooldown_interval = '3600'");
        $this->addSql("UPDATE alert_rules SET cooldown_interval = 'PT6H' WHERE cooldown_interval = '21600'");

        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT `FK_63C06ED789870488` FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE escalation_policies DROP FOREIGN KEY `FK_escalation_policies_monitor`');
        $this->addSql('ALTER TABLE escalation_policies RENAME INDEX idx_escalation_policies_channel TO IDX_E659FDB189870488');
        $this->addSql('DROP INDEX idx_monitor_owner ON monitors');
        $this->addSql('ALTER TABLE monitors CHANGE headers headers JSON DEFAULT NULL, CHANGE last_checked_at last_checked_at DATETIME DEFAULT NULL, CHANGE next_check_at next_check_at DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE last_status_change_at last_status_change_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_notification_channel_owner ON notification_channels');
        $this->addSql('ALTER TABLE notification_templates CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE notification_templates RENAME INDEX unique_template_name TO unique_name');
        $this->addSql('ALTER TABLE notification_templates RENAME INDEX unique_template_channel_event TO unique_channel_event');
        $this->addSql('ALTER TABLE ping_results CHANGE id id VARCHAR(36) NOT NULL, CHANGE monitor_id monitor_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ping_stats_daily CHANGE monitor_id monitor_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ping_stats_hourly CHANGE monitor_id monitor_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert_rules ADD target VARCHAR(255) NOT NULL, ADD last_triggered_at DATETIME DEFAULT NULL, ADD updated_at DATETIME NOT NULL, CHANGE failure_threshold failure_threshold INT NOT NULL, CHANGE cooldown_interval cooldown_interval INT DEFAULT 3600 NOT NULL, CHANGE notification_channel_id notification_channel_id VARCHAR(36) DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT `FK_63C06ED74CEAA4F1` FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_63C06ED74CEAA4F1 ON alert_rules (monitor_id)');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT `FK_escalation_policies_monitor` FOREIGN KEY (monitor_id) REFERENCES monitors (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_E659FDB14CE1C902 ON escalation_policies (monitor_id)');
        $this->addSql('ALTER TABLE escalation_policies RENAME INDEX idx_e659fdb189870488 TO idx_escalation_policies_channel');
        $this->addSql('ALTER TABLE monitors CHANGE headers headers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE last_checked_at last_checked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE next_check_at next_check_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_status_change_at last_status_change_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_monitor_owner ON monitors (owner_id)');
        $this->addSql('CREATE INDEX idx_notification_channel_owner ON notification_channels (owner_id)');
        $this->addSql('ALTER TABLE notification_templates CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE notification_templates RENAME INDEX unique_channel_event TO unique_template_channel_event');
        $this->addSql('ALTER TABLE notification_templates RENAME INDEX unique_name TO unique_template_name');
        $this->addSql('ALTER TABLE ping_results CHANGE id id VARCHAR(36) NOT NULL, CHANGE monitor_id monitor_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ping_stats_daily CHANGE monitor_id monitor_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE ping_stats_hourly CHANGE monitor_id monitor_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE `users` CHANGE roles roles JSON NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }
}
