<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;

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
        $table = $schema->createTable('ping_results');
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('monitor_id', Types::STRING, ['length' => 36]);
        $table->addColumn('status_code', Types::SMALLINT);
        $table->addColumn('latency_ms', Types::INTEGER);
        $table->addColumn('is_successful', Types::BOOLEAN);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);

        $table->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [UnqualifiedName::unquoted('id')], true));
        $table->addIndex(['monitor_id', 'created_at'], 'idx_monitor_created');

        // Initialize partitioning programmatically to avoid chicken-and-egg issues in handler
        // Using RANGE COLUMNS for better pruning and clarity
        // Wrap in platform check for SQLite compatibility during tests
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform) {
            $this->addSql("ALTER TABLE ping_results PARTITION BY RANGE COLUMNS(created_at) (
                PARTITION p_initial VALUES LESS THAN ('2026-01-01')
            )");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ping_results');
    }
}
