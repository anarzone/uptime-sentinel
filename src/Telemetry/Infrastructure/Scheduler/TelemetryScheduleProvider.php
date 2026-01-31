<?php

declare(strict_types=1);

namespace App\Telemetry\Infrastructure\Scheduler;

use App\Telemetry\Application\Message\IngestTelemetryMessage;
use App\Telemetry\Application\Message\MaintainPartitionsMessage;
use App\Telemetry\Application\Message\RollupDailyMessage;
use App\Telemetry\Application\Message\RollupHourlyMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Scheduler for telemetry tasks.
 *
 * - Ingestion: Every 10 seconds.
 * - Hourly Rollup: At :05 past every hour.
 * - Daily Rollup: At 00:10 every day.
 * - Partition Maintenance: At 01:00 every day.
 */
#[AsSchedule('telemetry')]
final readonly class TelemetryScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            // Ingest buffered check results from Redis
            ->add(RecurringMessage::every('10 seconds', new IngestTelemetryMessage()))

            // Aggregate ping_results into hourly stats (runs at :05 past every hour)
            ->add(RecurringMessage::cron('5 * * * *', new RollupHourlyMessage()))

            // Aggregate hourly stats into daily stats (runs at 00:10 every day)
            ->add(RecurringMessage::cron('10 0 * * *', new RollupDailyMessage()))

            // Manage partitions: add future, drop old (runs at 01:00 every day)
            ->add(RecurringMessage::cron('0 1 * * *', new MaintainPartitionsMessage()))
        ;
    }
}
