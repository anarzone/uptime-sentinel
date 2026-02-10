<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Controller;

use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/alert-rules')]
final class AdminAlertRuleController extends AbstractController
{
    public function __construct(
        private AlertRuleRepositoryInterface $alertRuleRepository,
    ) {
    }

    #[Route('', name: 'admin_alert_rules_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/alert_rule/index.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_alert_rules_edit', methods: ['GET'])]
    public function edit(string $id): Response
    {
        if (!$this->alertRuleRepository->findById($id)) {
            throw $this->createNotFoundException('Alert rule not found');
        }

        return $this->render('admin/alert_rule/edit.html.twig', ['id' => $id]);
    }
}
