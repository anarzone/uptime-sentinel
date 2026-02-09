<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig\Components;

use App\Telemetry\Infrastructure\Repository\TelemetryReadRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class LatencyChart
{
    public string $monitorId = 'global';

    public string $range = '24h';

    public function __construct(
        private readonly TelemetryReadRepository $telemetryRepository,
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly \Symfony\Bundle\SecurityBundle\Security $security
    ) {
    }

    public function getChart(): Chart
    {
        $start = new \DateTimeImmutable('-24 hours');
        $end = new \DateTimeImmutable();

        $user = $this->security->getUser();
        $ownerId = null;
        if (!$this->security->isGranted('ROLE_ADMIN') && $user instanceof \App\Security\Domain\Entity\User) {
            $ownerId = $user->getId()->toRfc4122();
        }

        // In a real app, we would parse $this->range and $this->monitorId
        // For the global dashboard, we can average across all monitors or show a specific one
        $data = $this->telemetryRepository->getGlobalStats($ownerId); // Updated for owner filtering

        // Since we are in 'global' mode, we pass null to get aggregated stats across all monitors
        $history = $this->telemetryRepository->getLatencyHistory(null, $start, $end, $ownerId);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $labels = [];
        $values = [];
        foreach ($history as $point) {
            $labels[] = (new \DateTimeImmutable($point['timestamp']))->format('H:i');
            $values[] = $point['value'];
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Latency (ms)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'borderColor' => '#6366f1',
                    'data' => $values,
                    'tension' => 0.4,
                    'fill' => true,
                    'pointRadius' => 0,
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(255, 255, 255, 0.05)'],
                    'ticks' => ['color' => '#94a3b8'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['color' => '#94a3b8', 'maxRotation' => 0],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ]);

        return $chart;
    }
}
