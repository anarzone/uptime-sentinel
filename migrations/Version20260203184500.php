<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Restores telemetry tables with MySQL Range Partitioning.
 * This fixes the accidental drop in the previous migration.
 */
final class Version20260203184500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restores ping_results (partitioned), ping_stats_hourly, and ping_stats_daily';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ping_results');
        $this->addSql('DROP TABLE IF EXISTS ping_stats_hourly');
        $this->addSql('DROP TABLE IF EXISTS ping_stats_daily');

        // 1. Re-create ping_results with Range Partitioning

        // Note: Partitioning column MUST be part of the Primary Key in MySQL
        $this->addSql("CREATE TABLE ping_results (
            id VARCHAR(36) NOT NULL,
            monitor_id VARCHAR(36) NOT NULL,
            status_code SMALLINT NOT NULL,
            latency_ms INT NOT NULL,
            is_successful TINYINT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id, created_at),
            INDEX idx_monitor_created (monitor_id, created_at)
        ) ENGINE=InnoDB
        PARTITION BY RANGE (TO_DAYS(created_at)) (
            PARTITION p2026_01 VALUES LESS THAN (TO_DAYS('2026-02-01')),
            PARTITION p2026_02 VALUES LESS THAN (TO_DAYS('2026-03-01')),
            PARTITION p2026_03 VALUES LESS THAN (TO_DAYS('2026-04-01')),
            PARTITION p2026_04 VALUES LESS THAN (TO_DAYS('2026-05-01')),
            PARTITION p_future VALUES LESS THAN MAXVALUE
        )");

        // 2. Re-create aggregate tables
        $this->addSql("CREATE TABLE ping_stats_hourly (
            monitor_id CHAR(36) NOT NULL,
            bucket_time DATETIME NOT NULL COMMENT 'Start of the hour',
            ping_count INT UNSIGNED NOT NULL DEFAULT 0,
            success_count INT UNSIGNED NOT NULL DEFAULT 0,
            avg_latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
            max_latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (monitor_id, bucket_time),
            INDEX idx_bucket_time (bucket_time)
        ) ENGINE=InnoDB");

        $this->addSql("CREATE TABLE ping_stats_daily (
            monitor_id CHAR(36) NOT NULL,
            bucket_time DATE NOT NULL COMMENT 'The day',
            ping_count INT UNSIGNED NOT NULL DEFAULT 0,
            success_count INT UNSIGNED NOT NULL DEFAULT 0,
            avg_latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
            max_latency_ms INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (monitor_id, bucket_time),
            INDEX idx_bucket_time (bucket_time)
        ) ENGINE=InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ping_results');
        $this->addSql('DROP TABLE ping_stats_hourly');
        $this->addSql('DROP TABLE ping_stats_daily');
    }
}
