<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates aggregate tables for telemetry rollup (Three-Tier Strategy).
 */
final class Version20260131172500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates ping_stats_hourly and ping_stats_daily aggregate tables';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        $isMysql = $platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform;
        $u = $isMysql ? ' UNSIGNED' : '';
        $commentH = $isMysql ? " COMMENT 'Start of the hour'" : '';
        $commentD = $isMysql ? " COMMENT 'The day'" : '';
        $index = $isMysql ? ', INDEX idx_bucket_time (bucket_time)' : '';

        // Tier 2: Hourly aggregates (6-12 months retention)
        $this->addSql("CREATE TABLE ping_stats_hourly (
            monitor_id CHAR(36) NOT NULL,
            bucket_time DATETIME NOT NULL$commentH,
            ping_count INT$u NOT NULL DEFAULT 0,
            success_count INT$u NOT NULL DEFAULT 0,
            avg_latency_ms INT$u NOT NULL DEFAULT 0,
            max_latency_ms INT$u NOT NULL DEFAULT 0,
            PRIMARY KEY (monitor_id, bucket_time)$index
        )");

        // Tier 3: Daily aggregates (indefinite retention)
        $this->addSql("CREATE TABLE ping_stats_daily (
            monitor_id CHAR(36) NOT NULL,
            bucket_time DATE NOT NULL$commentD,
            ping_count INT$u NOT NULL DEFAULT 0,
            success_count INT$u NOT NULL DEFAULT 0,
            avg_latency_ms INT$u NOT NULL DEFAULT 0,
            max_latency_ms INT$u NOT NULL DEFAULT 0,
            PRIMARY KEY (monitor_id, bucket_time)$index
        )");

        if (!$isMysql) {
            $this->addSql('CREATE INDEX idx_bucket_time_hourly ON ping_stats_hourly (bucket_time)');
            $this->addSql('CREATE INDEX idx_bucket_time_daily ON ping_stats_daily (bucket_time)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ping_stats_daily');
        $this->addSql('DROP TABLE ping_stats_hourly');
    }
}
