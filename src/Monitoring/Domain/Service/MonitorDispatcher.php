<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Service;

use App\Monitoring\Application\Command\CheckMonitorBatch\CheckMonitorBatchCommand;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MonitorDispatcher
{
    public function __construct(
        private MonitorRepositoryInterface $monitorRepository,
        private MessageBusInterface $messageBus
    ) {
    }

    public function dispatchDueMonitors(): int
    {
        $dueMonitors = $this->monitorRepository->findDueMonitors();
        $monitorIds = [];

        foreach ($dueMonitors as $monitor) {
            $monitorIds[] = $monitor->id->toString();
        }

        // Chunk IDs into batches of 50
        $batches = array_chunk($monitorIds, 50);

        foreach ($batches as $batch) {
            $this->messageBus->dispatch(new CheckMonitorBatchCommand($batch));
        }

        return \count($dueMonitors);
    }
}
