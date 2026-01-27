<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126183543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Rename columns in referenced tables first
        $this->addSql('ALTER TABLE monitors CHANGE id_value uuid VARCHAR(36) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (uuid)');
        $this->addSql('ALTER TABLE notification_channels CHANGE id_value uuid VARCHAR(36) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (uuid)');

        // 2. Drop old foreign keys and indices
        $this->addSql('ALTER TABLE alert_rules DROP FOREIGN KEY `FK_63C06ED789870488`');
        $this->addSql('ALTER TABLE escalation_policies DROP FOREIGN KEY `FK_E659FDB189870488`');
        $this->addSql('DROP INDEX idx_monitor_level ON escalation_policies');

        // 3. Change columns in dependent tables
        $this->addSql('ALTER TABLE alert_rules CHANGE id_value uuid VARCHAR(36) NOT NULL, CHANGE monitor_id_value monitor_id_uuid VARCHAR(36) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (uuid, monitor_id_uuid)');
        $this->addSql('ALTER TABLE escalation_policies CHANGE id_value uuid VARCHAR(36) NOT NULL, CHANGE monitor_id_value monitor_id_uuid VARCHAR(36) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (uuid, monitor_id_uuid)');

        // 4. Re-add foreign keys and indices
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT FK_63C06ED789870488 FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (uuid)');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT FK_E659FDB189870488 FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (uuid)');
        $this->addSql('CREATE INDEX idx_monitor_level ON escalation_policies (monitor_id_uuid, level)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alert_rules DROP FOREIGN KEY FK_63C06ED789870488');
        $this->addSql('ALTER TABLE alert_rules ADD id_value VARCHAR(36) NOT NULL, ADD monitor_id_value VARCHAR(36) NOT NULL, DROP uuid, DROP monitor_id_uuid, DROP PRIMARY KEY, ADD PRIMARY KEY (id_value, monitor_id_value)');
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT `FK_63C06ED789870488` FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id_value) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE escalation_policies DROP FOREIGN KEY FK_E659FDB189870488');
        $this->addSql('DROP INDEX idx_monitor_level ON escalation_policies');
        $this->addSql('ALTER TABLE escalation_policies ADD id_value VARCHAR(36) NOT NULL, ADD monitor_id_value VARCHAR(36) NOT NULL, DROP uuid, DROP monitor_id_uuid, DROP PRIMARY KEY, ADD PRIMARY KEY (id_value, monitor_id_value)');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT `FK_E659FDB189870488` FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id_value) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX idx_monitor_level ON escalation_policies (monitor_id_value, level)');
        $this->addSql('ALTER TABLE monitors CHANGE uuid id_value VARCHAR(36) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_value)');
        $this->addSql('ALTER TABLE notification_channels CHANGE uuid id_value VARCHAR(36) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_value)');
    }
}
