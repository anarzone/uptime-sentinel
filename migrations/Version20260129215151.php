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
        // Use raw SQL to ensure table creation and partitioning happen in correct order
        // Schema API would defer table creation until after this method completes
        $this->addSql('CREATE TABLE ping_results (
            id CHAR(36) NOT NULL,
            monitor_id CHAR(36) NOT NULL,
            status_code SMALLINT NOT NULL,
            latency_ms INT NOT NULL,
            is_successful TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id, created_at)
        )');
        $this->addSql('CREATE INDEX idx_monitor_created ON ping_results (monitor_id, created_at)');

        // Add partitioning for MySQL (Skipped for SQLite during tests)
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform) {
            $this->addSql("ALTER TABLE ping_results PARTITION BY RANGE COLUMNS(created_at) (
                PARTITION p_initial VALUES LESS THAN ('2026-01-01'),
                PARTITION p_future VALUES LESS THAN (MAXVALUE)
            )");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ping_results');
    }
}
