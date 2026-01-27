<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Controller;

use App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notification-channels')]
final class NotificationChannelController extends AbstractController
{
    public function __construct(
        private NotificationChannelRepositoryInterface $channelRepository,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $channels = $this->channelRepository->findAll();

        $data = array_map(fn ($c) => [
            'id' => $c->id->toString(),
            'name' => $c->name,
            'type' => $c->type->value,
            'dsn' => $c->dsn,
            'isEnabled' => $c->isEnabled,
            'createdAt' => $c->createdAt->format(\DateTimeInterface::ATOM),
        ], $channels);

        return new JsonResponse(['data' => $data]);
    }
}
