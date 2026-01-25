<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120170739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alert_rules (id VARCHAR(36) NOT NULL, channel VARCHAR(20) NOT NULL, target VARCHAR(255) NOT NULL, consecutive_failures INT DEFAULT 3 NOT NULL, is_enabled TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, monitor_id_value VARCHAR(36) NOT NULL, PRIMARY KEY (id, monitor_id_value)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE monitors (name VARCHAR(255) NOT NULL, method VARCHAR(255) NOT NULL, interval_seconds INT NOT NULL, timeout_seconds INT NOT NULL, status VARCHAR(255) NOT NULL, expected_status_code INT NOT NULL, headers JSON DEFAULT NULL, body LONGTEXT DEFAULT NULL, last_checked_at DATETIME DEFAULT NULL, next_check_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id_value VARCHAR(36) NOT NULL, url_value VARCHAR(255) NOT NULL, PRIMARY KEY (id_value)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE alert_rules');
        $this->addSql('DROP TABLE monitors');
    }
}
