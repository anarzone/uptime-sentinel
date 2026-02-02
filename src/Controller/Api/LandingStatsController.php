<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Telemetry\Infrastructure\Repository\TelemetryReadRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class LandingStatsController extends AbstractController
{
    public function __construct(
        private readonly TelemetryReadRepository $telemetryRepository,
        private readonly Connection $connection
    ) {
    }

    #[Route('/landing-stats', name: 'landing_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $globalStats = $this->telemetryRepository->getGlobalStats();

        // Count total notification channels
        $channelsCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM notification_channels');

        return new JsonResponse([
            'total_monitors' => $globalStats['total_monitors'] ?? 0,
            'total_integrations' => $channelsCount,
            'avg_latency_ms' => round((float) ($globalStats['avg_latency_24h'] ?? 0), 2),
            'uptime_percentage' => round(100 * (($globalStats['up_count'] ?? 0) / max(1, $globalStats['total_monitors'] ?? 1)), 2),
        ]);
    }
}
