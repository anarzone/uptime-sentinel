<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig\Components;

use App\Monitoring\Application\Command\AlertRule\UpdateAlertRuleCommand;
use App\Monitoring\Domain\Model\Alert\AlertRule;
use App\Monitoring\Domain\Repository\AlertRuleRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class EditAlertRule extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $id;

    #[LiveProp(writable: true)]
    public string $target = '';

    #[LiveProp(writable: true)]
    public int $failureThreshold = 3;

    #[LiveProp(writable: true)]
    public string $notificationType = 'failure';

    #[LiveProp(writable: true)]
    public string $channelType = 'email';

    #[LiveProp(writable: true)]
    public ?string $cooldownInterval = null;

    public function __construct(
        private readonly AlertRuleRepositoryInterface $alertRuleRepository,
        private readonly MessageBusInterface $bus
    ) {
    }

    public function mount(string $id): void
    {
        $this->id = $id;
        $rule = $this->alertRuleRepository->findById($id);

        if (!$rule) {
            throw $this->createNotFoundException();
        }

        $this->target = $rule->notificationChannel->dsn;
        $this->failureThreshold = $rule->failureThreshold;
        $this->notificationType = $rule->type->value;
        $this->channelType = $rule->notificationChannel->type->value;
        $this->cooldownInterval = $rule->cooldownInterval;
    }

    #[LiveAction]
    public function save(): Response
    {
        $user = $this->getUser();
        $requesterId = $user instanceof \App\Security\Domain\Entity\User ? $user->getId()->toRfc4122() : $user?->getUserIdentifier();

        try {
            $command = new UpdateAlertRuleCommand(
                id: $this->id,
                target: $this->target,
                failureThreshold: $this->failureThreshold,
                type: $this->notificationType,
                cooldownInterval: $this->cooldownInterval,
                channelType: $this->channelType,
                requesterId: $requesterId
            );

            $this->bus->dispatch($command);

            $this->addFlash('success', 'Alert rule updated successfully.');

            return $this->redirectToRoute('admin_alert_rules_index');

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_alert_rules_edit', ['id' => $this->id]);
    }

    public function getRule(): ?AlertRule
    {
        return $this->alertRuleRepository->findById($this->id);
    }
}
