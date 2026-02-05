<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\CreateMonitor;

use App\Monitoring\Domain\Model\Monitor\HttpMethod;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Model\Monitor\MonitorStatus;
use App\Monitoring\Domain\Model\Monitor\Url;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateMonitorHandler
{
    public function __construct(
        private MonitorRepositoryInterface $monitorRepository,
        private \Symfony\Bundle\SecurityBundle\Security $security,
    ) {
    }

    public function __invoke(CreateMonitorCommand $command): void
    {
        $now = new \DateTimeImmutable();

        if ($command->requesterId === null) {
            throw new \InvalidArgumentException('requesterId is required');
        }

        // Basic authorization: ensure requester matches owner if both provided
        if ($command->ownerId !== null) {
            if ($command->requesterId !== $command->ownerId && !$this->security->isGranted('ROLE_ADMIN')) {
                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Cannot create monitor for another user.');
            }
        }

        $monitor = new Monitor(
            id: MonitorId::fromString($command->uuid),
            name: $command->name,
            url: Url::fromString($command->url),
            method: HttpMethod::from($command->method),
            intervalSeconds: $command->intervalSeconds,
            timeoutSeconds: $command->timeoutSeconds,
            status: MonitorStatus::ACTIVE,
            expectedStatusCode: $command->expectedStatusCode,
            headers: $command->headers,
            body: $command->body,
            lastCheckedAt: null,
            nextCheckAt: $now->modify("+{$command->intervalSeconds} seconds"),
            createdAt: $now,
            updatedAt: $now,
            ownerId: $command->ownerId !== null ? \App\Monitoring\Domain\ValueObject\OwnerId::fromString($command->ownerId) : null
        );

        $this->monitorRepository->save($monitor);
    }
}
