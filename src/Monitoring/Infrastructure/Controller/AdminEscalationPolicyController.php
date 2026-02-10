<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Controller;

use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/escalation-policies')]
final class AdminEscalationPolicyController extends AbstractController
{
    public function __construct(
        private EscalationPolicyRepositoryInterface $policyRepository,
    ) {
    }

    #[Route('', methods: ['GET'], name: 'admin_escalation_policies_index')]
    public function index(): Response
    {
        return $this->render('admin/escalation_policy/index.html.twig');
    }

    #[Route('/{id}/edit', methods: ['GET'], name: 'admin_escalation_policies_edit')]
    public function edit(string $id): Response
    {
        if (!$this->policyRepository->findById($id)) {
            throw $this->createNotFoundException('Escalation policy not found');
        }

        return $this->render('admin/escalation_policy/edit.html.twig', ['id' => $id]);
    }
}
