<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Controller;

use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/monitors')]
final class AdminMonitorController extends AbstractController
{
    public function __construct(
        private MonitorRepositoryInterface $monitorRepository,
    ) {
    }

    #[Route('', name: 'admin_monitors_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/monitor/index.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_monitors_edit', methods: ['GET'])]
    public function edit(string $id): Response
    {
        if (!$this->monitorRepository->exists($id)) {
            throw $this->createNotFoundException('Monitor not found');
        }

        return $this->render('admin/monitor/edit.html.twig', ['id' => $id]);
    }
}
