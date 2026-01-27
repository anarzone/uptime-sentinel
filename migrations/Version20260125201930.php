<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125201930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification_channels (name VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, dsn LONGTEXT NOT NULL, is_enabled TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, id_value VARCHAR(36) NOT NULL, PRIMARY KEY (id_value)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE alert_rules ADD notification_channel_id VARCHAR(36) NOT NULL, DROP channel, DROP target');
        $this->addSql('ALTER TABLE alert_rules ADD CONSTRAINT FK_63C06ED789870488 FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id_value)');
        $this->addSql('CREATE INDEX IDX_63C06ED789870488 ON alert_rules (notification_channel_id)');
        $this->addSql('ALTER TABLE escalation_policies ADD notification_channel_id VARCHAR(36) NOT NULL, DROP channel, DROP target');
        $this->addSql('ALTER TABLE escalation_policies ADD CONSTRAINT FK_E659FDB189870488 FOREIGN KEY (notification_channel_id) REFERENCES notification_channels (id_value)');
        $this->addSql('CREATE INDEX IDX_E659FDB189870488 ON escalation_policies (notification_channel_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE notification_channels');
        $this->addSql('ALTER TABLE alert_rules DROP FOREIGN KEY FK_63C06ED789870488');
        $this->addSql('DROP INDEX IDX_63C06ED789870488 ON alert_rules');
        $this->addSql('ALTER TABLE alert_rules ADD channel VARCHAR(20) NOT NULL, ADD target VARCHAR(255) NOT NULL, DROP notification_channel_id');
        $this->addSql('ALTER TABLE escalation_policies DROP FOREIGN KEY FK_E659FDB189870488');
        $this->addSql('DROP INDEX IDX_E659FDB189870488 ON escalation_policies');
        $this->addSql('ALTER TABLE escalation_policies ADD channel VARCHAR(20) NOT NULL, ADD target VARCHAR(255) NOT NULL, DROP notification_channel_id');
    }
}
