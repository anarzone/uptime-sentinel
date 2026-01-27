<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Controller;

use App\Monitoring\Application\Command\CheckMonitor\CheckMonitorCommand;
use App\Monitoring\Application\Command\CreateMonitor\CreateMonitorCommand;
use App\Monitoring\Application\Command\UpdateMonitor\UpdateMonitorCommand;
use App\Monitoring\Application\Dto\CreateMonitorRequestDto;
use App\Monitoring\Application\Dto\UpdateMonitorRequestDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\UuidV7;

#[Route('/api/monitors')]
class MonitorController extends AbstractController
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateMonitorRequestDto $dto): JsonResponse
    {
        $uuid = new UuidV7();

        $command = new CreateMonitorCommand(
            uuid: $uuid->toRfc4122(),
            name: $dto->name,
            url: $dto->url,
            method: $dto->method,
            intervalSeconds: $dto->intervalSeconds,
            timeoutSeconds: $dto->timeoutSeconds,
            expectedStatusCode: $dto->expectedStatusCode,
            headers: $dto->headers,
            body: $dto->body
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Monitor creation request accepted',
            'data' => [
                'monitorId' => $uuid,
            ],
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{uuid}', methods: ['PUT'])]
    public function update(string $uuid, #[MapRequestPayload] UpdateMonitorRequestDto $dto): JsonResponse
    {
        $command = new UpdateMonitorCommand(
            uuid: $uuid,
            name: $dto->name,
            url: $dto->url,
            method: $dto->method,
            intervalSeconds: $dto->intervalSeconds,
            timeoutSeconds: $dto->timeoutSeconds,
            expectedStatusCode: $dto->expectedStatusCode,
            headers: $dto->headers,
            body: $dto->body
        );

        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Monitor update request accepted',
            'data' => [
                'monitorId' => $uuid,
            ],
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{uuid}/check', methods: ['POST'])]
    public function check(string $uuid): JsonResponse
    {
        $command = new CheckMonitorCommand($uuid);
        $this->bus->dispatch($command);

        return new JsonResponse([
            'message' => 'Monitor check request accepted',
            'data' => [
                'monitorId' => $uuid,
            ],
        ], Response::HTTP_ACCEPTED);
    }
}
