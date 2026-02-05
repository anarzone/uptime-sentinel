<?php

declare(strict_types=1);

namespace App\Monitoring\Application\Command\DeleteMonitor;

use App\Monitoring\Application\Service\MonitorAuthorizationService;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\ValueObject\OwnerId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteMonitorHandler
{
    public function __construct(
        private MonitorRepositoryInterface $monitorRepository,
        private MonitorAuthorizationService $authorizationService,
        private Security $security,
    ) {
    }

    public function __invoke(DeleteMonitorCommand $command): void
    {
        if ($command->requesterId === null) {
            throw new \InvalidArgumentException('requesterId is required');
        }

        $monitor = $this->monitorRepository->find(MonitorId::fromString($command->uuid));

        if ($monitor === null) {
            throw new \InvalidArgumentException(\sprintf('Monitor with ID "%s" does not exist', $command->uuid));
        }

        // Authorization check (Fix TOCTOU)
        $this->authorizationService->requireOwnership(
            $monitor,
            OwnerId::fromString($command->requesterId),
            $this->security->isGranted('ROLE_ADMIN')
        );

        $this->monitorRepository->remove($monitor);
    }
}
