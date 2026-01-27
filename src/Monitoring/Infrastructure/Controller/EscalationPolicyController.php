<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Controller;

use App\Monitoring\Application\Command\EscalationPolicy\CreateEscalationPolicyCommand;
use App\Monitoring\Application\Command\EscalationPolicy\DeleteEscalationPolicyCommand;
use App\Monitoring\Application\Command\EscalationPolicy\DisableEscalationPolicyCommand;
use App\Monitoring\Application\Command\EscalationPolicy\EnableEscalationPolicyCommand;
use App\Monitoring\Application\Dto\CreateEscalationPolicyRequestDto;
use App\Monitoring\Application\Dto\EscalationPolicyResponseDto;
use App\Monitoring\Domain\Model\Monitor\MonitorId;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/escalation-policies')]
final class EscalationPolicyController extends AbstractController
{
    public function __construct(
        private EscalationPolicyRepositoryInterface $escalationPolicyRepository,
        private MonitorRepositoryInterface $monitorRepository,
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateEscalationPolicyRequestDto $dto): JsonResponse
    {
        $command = CreateEscalationPolicyCommand::create(
            $dto->monitorId,
            $dto->level,
            $dto->consecutiveFailures,
            $dto->channel,
            $dto->target,
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Escalation policy creation request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/monitor/{monitorId}', methods: ['GET'])]
    public function listForMonitor(string $monitorId): JsonResponse
    {
        $monitorId = MonitorId::fromString($monitorId);

        // Verify monitor exists
        $monitor = $this->monitorRepository->find($monitorId);
        if ($monitor === null) {
            return new JsonResponse([
                'error' => 'Monitor not found',
                'message' => \sprintf('Monitor with ID "%s" does not exist', $monitorId->toString()),
            ], Response::HTTP_NOT_FOUND);
        }

        // Get applicable policies (monitor-specific + global)
        $policies = $this->escalationPolicyRepository->findApplicableForMonitor($monitorId);

        return new JsonResponse([
            'data' => EscalationPolicyResponseDto::fromEntities($policies),
        ]);
    }

    #[Route('', methods: ['GET'])]
    public function listAll(): JsonResponse
    {
        $policies = $this->escalationPolicyRepository->findAll();

        return new JsonResponse([
            'data' => EscalationPolicyResponseDto::fromEntities($policies),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        /** @var \App\Monitoring\Domain\Model\Alert\EscalationPolicy|null $policy */
        $policy = $this->escalationPolicyRepository->find($id);

        if ($policy === null) {
            return new JsonResponse([
                'error' => 'Escalation policy not found',
                'message' => \sprintf('Escalation policy with ID "%s" does not exist', $id),
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'data' => EscalationPolicyResponseDto::fromEntity($policy),
        ]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $command = new DeleteEscalationPolicyCommand($id);
        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Escalation policy deletion request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/enable', methods: ['PATCH'])]
    public function enable(string $id): JsonResponse
    {
        $command = new EnableEscalationPolicyCommand($id);
        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Escalation policy enable request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/disable', methods: ['PATCH'])]
    public function disable(string $id): JsonResponse
    {
        $command = new DisableEscalationPolicyCommand($id);
        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Escalation policy disable request accepted',
        ], Response::HTTP_ACCEPTED);
    }
}
