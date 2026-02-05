<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205160355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes for owner_id in monitors and notification_channels';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_monitor_owner ON monitors (owner_id)');
        $this->addSql('CREATE INDEX idx_notification_channel_owner ON notification_channels (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_monitor_owner ON monitors');
        $this->addSql('DROP INDEX idx_notification_channel_owner ON notification_channels');
    }
}
