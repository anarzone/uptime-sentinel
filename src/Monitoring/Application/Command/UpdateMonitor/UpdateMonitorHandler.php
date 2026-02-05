<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\UpdateMonitor;

use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\Url;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdateMonitorHandler
{
    public function __construct(
        private MonitorRepositoryInterface $monitorRepository,
        private \App\Monitoring\Application\Service\MonitorAuthorizationService $authorizationService,
        private \Symfony\Bundle\SecurityBundle\Security $security,
    ) {
    }

    public function __invoke(UpdateMonitorCommand $command): void
    {
        $monitorId = MonitorId::fromString($command->uuid);
        $monitor = $this->monitorRepository->find($monitorId);

        if (!$monitor) {
            throw new \InvalidArgumentException('Monitor not found');
        }

        // Authorization check (Fix TOCTOU)
        if ($command->requesterId === null) {
            throw new \InvalidArgumentException('requesterId is required');
        }

        $this->authorizationService->requireOwnership(
            $monitor,
            \App\Monitoring\Domain\ValueObject\OwnerId::fromString($command->requesterId),
            $this->security->isGranted('ROLE_ADMIN')
        );

        $name = $command->name ?? $monitor->name;
        $url = $command->url !== null
            ? Url::fromString($command->url)
            : $monitor->url;
        $method = $command->method !== null
            ? HttpMethod::from($command->method)
            : $monitor->method;
        $intervalSeconds = $command->intervalSeconds ?? $monitor->intervalSeconds;
        $timeoutSeconds = $command->timeoutSeconds ?? $monitor->timeoutSeconds;
        $expectedStatusCode = $command->expectedStatusCode ?? $monitor->expectedStatusCode;
        $headers = $command->headers ?? $monitor->headers;
        $body = $command->body ?? $monitor->body;

        $monitor->updateConfiguration(
            name: $name,
            url: $url,
            method: $method,
            intervalSeconds: $intervalSeconds,
            timeoutSeconds: $timeoutSeconds,
            expectedStatusCode: $expectedStatusCode,
            headers: $headers,
            body: $body
        );

        $this->monitorRepository->save($monitor);
    }
}
