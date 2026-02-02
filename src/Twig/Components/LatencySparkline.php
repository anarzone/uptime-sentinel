<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Telemetry\Infrastructure\Repository\TelemetryReadRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class LatencySparkline
{
    public string $monitorId;

    public function __construct(
        private readonly TelemetryReadRepository $telemetryRepository,
        private readonly ChartBuilderInterface $chartBuilder
    ) {
    }

    public function getChart(): Chart
    {
        $start = new \DateTimeImmutable('-1 hour');
        $end = new \DateTimeImmutable();

        $history = $this->telemetryRepository->getLatencyHistory($this->monitorId, $start, $end);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $values = array_column($history, 'value');
        $labels = array_fill(0, \count($values), '');

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'backgroundColor' => 'transparent',
                    'borderColor' => '#6366f1',
                    'data' => $values,
                    'tension' => 0.4,
                    'pointRadius' => 0,
                    'borderWidth' => 2,
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false, 'beginAtZero' => true],
            ],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['enabled' => false],
            ],
        ]);

        return $chart;
    }
}
