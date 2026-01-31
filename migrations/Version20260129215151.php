<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129215151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates partitioned ping_results table for high-throughput telemetry data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ping_results (
            id VARCHAR(36) NOT NULL,
            monitor_id CHAR(36) NOT NULL,
            status_code SMALLINT NOT NULL,
            latency_ms INT NOT NULL,
            is_successful TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id, created_at),
            INDEX idx_monitor_created (monitor_id, created_at)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB
        PARTITION BY RANGE (YEAR(created_at)) (
            PARTITION p2024 VALUES LESS THAN (2025),
            PARTITION p2025 VALUES LESS THAN (2026),
            PARTITION p2026 VALUES LESS THAN (2027),
            PARTITION p2027 VALUES LESS THAN (2028),
            PARTITION future VALUES LESS THAN MAXVALUE
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ping_results');
    }
}
