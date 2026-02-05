<?php

declare(strict_types=1);

namespace App\Telemetry\Infrastructure\Controller\Api;

use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Telemetry\Infrastructure\Repository\TelemetryReadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class StatusController extends AbstractController
{
    public function __construct(
        private readonly MonitorRepositoryInterface $monitorRepository,
        private readonly TelemetryReadRepository $telemetryRepository
    ) {
    }

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function getStatus(): JsonResponse
    {
        $monitors = $this->monitorRepository->findAll();
        $latencies = $this->telemetryRepository->getBulkLatencyAverages();
        $data = [];

        foreach ($monitors as $monitor) {
            $monitorId = $monitor->id->value;
            $data[] = [
                'id' => $monitorId,
                'name' => $monitor->name,
                'status' => $monitor->healthStatus->value,
                'last_checked' => $monitor->lastCheckedAt?->format('Y-m-d H:i:s'),
                'latency_avg_24h' => round($latencies[$monitorId] ?? 0, 2),
            ];
        }

        return new JsonResponse([
            'system_status' => $this->getOverallSystemStatus($data),
            'monitors' => $data,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    private function getOverallSystemStatus(array $monitors): string
    {
        $total = \count($monitors);
        if ($total === 0) {
            return 'unknown';
        }

        $up = \count(array_filter($monitors, fn ($m) => $m['status'] === 'up'));

        if ($up === $total) {
            return 'operational';
        }
        if ($up > 0) {
            return 'partial_outage';
        }

        return 'major_outage';
    }
}
