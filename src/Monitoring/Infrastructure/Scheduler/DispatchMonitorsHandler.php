<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Scheduler;

use App\Monitoring\Domain\Service\MonitorDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DispatchMonitorsHandler
{
    public function __construct(
        private MonitorDispatcher $dispatcher,
    ) {
    }

    public function __invoke(DispatchMonitorsMessage $message): void
    {
        $this->dispatcher->dispatchDueMonitors();
    }
}
