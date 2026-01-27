<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Controller;

use App\Monitoring\Application\Command\AlertRule\CreateAlertRuleCommand;
use App\Monitoring\Application\Command\AlertRule\DeleteAlertRuleCommand;
use App\Monitoring\Application\Command\AlertRule\DisableAlertRuleCommand;
use App\Monitoring\Application\Command\AlertRule\EnableAlertRuleCommand;
use App\Monitoring\Application\Command\AlertRule\UpdateAlertRuleCommand;
use App\Monitoring\Application\Dto\AlertRuleResponseDto;
use App\Monitoring\Application\Dto\CreateAlertRuleRequestDto;
use App\Monitoring\Application\Dto\UpdateAlertRuleRequestDto;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/alert-rules')]
final class AlertRuleController extends AbstractController
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private MonitorRepositoryInterface $monitorRepository,
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateAlertRuleRequestDto $dto): JsonResponse
    {
        $command = CreateAlertRuleCommand::create(
            $dto->monitorId,
            $dto->channel,
            $dto->target,
            $dto->failureThreshold,
            $dto->type,
            $dto->cooldownInterval,
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule creation request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/monitor/{monitorId}', methods: ['GET'])]
    public function listForMonitor(string $monitorId): JsonResponse
    {
        $monitorId = \App\Monitoring\Domain\Model\Monitor\MonitorId::fromString($monitorId);

        // Verify monitor exists
        $monitor = $this->monitorRepository->find($monitorId);
        if ($monitor === null) {
            return new JsonResponse([
                'error' => 'Monitor not found',
                'message' => \sprintf('Monitor with ID "%s" does not exist', $monitorId->toString()),
            ], Response::HTTP_NOT_FOUND);
        }

        $alertRules = $this->alertRuleRepository->findByMonitorId($monitorId);

        return new JsonResponse([
            'data' => AlertRuleResponseDto::fromEntities($alertRules),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $alertRule = $this->alertRuleRepository->find($id);

        if ($alertRule === null) {
            return new JsonResponse([
                'error' => 'Alert rule not found',
                'message' => \sprintf('Alert rule with ID "%s" does not exist', $id),
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'data' => AlertRuleResponseDto::fromEntity($alertRule),
        ]);
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(string $id, #[MapRequestPayload] UpdateAlertRuleRequestDto $dto): JsonResponse
    {
        $command = new UpdateAlertRuleCommand(
            $id,
            $dto->target,
            $dto->failureThreshold,
            $dto->type,
            $dto->cooldownInterval,
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule update request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $command = new DeleteAlertRuleCommand($id);
        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule deletion request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/enable', methods: ['PATCH'])]
    public function enable(string $id): JsonResponse
    {
        $command = new EnableAlertRuleCommand($id);
        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule enable request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/disable', methods: ['PATCH'])]
    public function disable(string $id): JsonResponse
    {
        $command = new DisableAlertRuleCommand($id);
        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule disable request accepted',
        ], Response::HTTP_ACCEPTED);
    }
}
