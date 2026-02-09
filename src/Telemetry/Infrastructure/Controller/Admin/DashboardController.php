<?php

declare(strict_types=1);

namespace App\Telemetry\Infrastructure\Controller\Admin;

use App\Telemetry\Infrastructure\Repository\TelemetryReadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly TelemetryReadRepository $telemetryRepository,
        private readonly \Symfony\Bundle\SecurityBundle\Security $security
    ) {
    }

    #[Route('', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->security->getUser();
        $ownerId = null;

        if (!$this->security->isGranted('ROLE_ADMIN') && $user instanceof \App\Security\Domain\Entity\User) {
            $ownerId = $user->getId()->toRfc4122();
        }

        $stats = $this->telemetryRepository->getGlobalStats($ownerId);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => [
                'total_monitors' => $stats['total_monitors'] ?? 0,
                'up_count' => $stats['up_count'] ?? 0,
                'down_count' => $stats['down_count'] ?? 0,
                'uptime_avg' => round(100 * (($stats['up_count'] ?? 0) / max(1, $stats['total_monitors'] ?? 1)), 2),
                'avg_latency_24h' => round((float) ($stats['avg_latency_24h'] ?? 0), 2),
            ],
        ]);
    }
}
