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
use App\Shared\Infrastructure\Controller\ControllerUserIdTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/alert-rules')]
final class AlertRuleController extends AbstractController
{
    use ControllerUserIdTrait;

    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
        private MonitorRepositoryInterface $monitorRepository,
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateAlertRuleRequestDto $dto): JsonResponse
    {
        $requesterId = $this->getRequesterId();

        $command = CreateAlertRuleCommand::create(
            monitorId: $dto->monitorId,
            channel: $dto->channel,
            target: $dto->target,
            failureThreshold: $dto->failureThreshold,
            type: $dto->type,
            cooldownInterval: $dto->cooldownInterval,
            ownerId: $requesterId,
            requesterId: $requesterId
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Created alert rule',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/batch', methods: ['POST'])]
    public function batchCreate(#[MapRequestPayload] \App\Monitoring\Application\Dto\BatchCreateAlertRuleRequestDto $dto): JsonResponse
    {
        $requesterId = $this->getRequesterId();

        foreach ($dto->monitorIds as $monitorId) {
            $command = CreateAlertRuleCommand::create(
                monitorId: $monitorId,
                channel: $dto->channel,
                target: $dto->target,
                failureThreshold: $dto->failureThreshold,
                type: $dto->type,
                cooldownInterval: $dto->cooldownInterval,
                ownerId: $requesterId,
                requesterId: $requesterId
            );

            $this->bus->dispatch($command);
        }

        return new JsonResponse([
            'message' => \sprintf('Created %d alert rules', \count($dto->monitorIds)),
            'count' => \count($dto->monitorIds),
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/monitor/{monitorId}', methods: ['GET'])]
    public function listForMonitor(string $monitorId): JsonResponse
    {
        $monitorIdObj = \App\Monitoring\Domain\Model\Monitor\MonitorId::fromString($monitorId);
        $alertRules = $this->alertRuleRepository->findByMonitorId($monitorIdObj);

        return new JsonResponse([
            'data' => AlertRuleResponseDto::fromEntities($alertRules),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $alertRule = $this->alertRuleRepository->find($id);

        if ($alertRule === null) {
            throw $this->createNotFoundException('Alert rule not found');
        }

        // We'll keep a basic check or use repository filtering.
        // For now, let's keep it simple and safe.
        $monitor = $this->monitorRepository->find($alertRule->monitorId);
        if (!$this->isGranted('ROLE_ADMIN') && $monitor?->ownerId !== $this->getRequesterId()) {
            throw $this->createAccessDeniedException('You do not have access to this alert rule.');
        }

        return new JsonResponse([
            'data' => AlertRuleResponseDto::fromEntity($alertRule),
        ]);
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(string $id, #[MapRequestPayload] UpdateAlertRuleRequestDto $dto): JsonResponse
    {
        $user = $this->getUser();
        $command = new UpdateAlertRuleCommand(
            id: $id,
            target: $dto->target,
            failureThreshold: $dto->failureThreshold,
            type: $dto->type,
            cooldownInterval: $dto->cooldownInterval,
            requesterId: $this->getRequesterId()
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule update request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $user = $this->getUser();
        $command = new DeleteAlertRuleCommand(
            id: $id,
            requesterId: $this->getRequesterId()
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule deletion request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/enable', methods: ['PATCH'])]
    public function enable(string $id): JsonResponse
    {
        $user = $this->getUser();
        $command = new EnableAlertRuleCommand(
            id: $id,
            requesterId: $this->getRequesterId()
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule enable request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/disable', methods: ['PATCH'])]
    public function disable(string $id): JsonResponse
    {
        $user = $this->getUser();
        $command = new DisableAlertRuleCommand(
            id: $id,
            requesterId: $this->getRequesterId()
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Alert rule disable request accepted',
        ], Response::HTTP_ACCEPTED);
    }
}
