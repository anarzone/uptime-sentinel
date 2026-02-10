<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig\Components;

use App\Monitoring\Application\Command\EscalationPolicy\UpdateEscalationPolicyCommand; // This command might not exist, need to check
use App\Monitoring\Domain\Model\Alert\EscalationPolicy;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class EditEscalationPolicy extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $id;

    #[LiveProp(writable: true)]
    public int $level = 1;

    #[LiveProp(writable: true)]
    public int $consecutiveFailures = 3;

    #[LiveProp(writable: true)]
    public string $channelType = 'email';

    #[LiveProp(writable: true)]
    public string $target = '';

    // We cannot easily update the Channel entity type/target from here without logic.
    // The policy holds a reference to NotificationChannel.
    // Changing channel means selecting a different channel OR updating the existing one?
    // Probably separate concerns: Policy associates with Channel.
    // But UI shows "Channel Type: Email, Target: foo@bar".
    // Does Policy "own" the channel instance or link to a shared one?
    // It seems CreateEscalationPolicyCommand creates a channel "on the fly" or reuses?
    // Let's check CreateEscalationPolicyHandler.

    // For now, let's assume we update the fields.

    public function __construct(
        private readonly EscalationPolicyRepositoryInterface $policyRepository,
    ) {
    }

    public function mount(string $id): void
    {
        $this->id = $id;
        $policy = $this->policyRepository->findById($id);

        if (!$policy) {
            throw $this->createNotFoundException();
        }

        $this->level = $policy->level;
        $this->consecutiveFailures = $policy->consecutiveFailures;
        $this->channelType = $policy->notificationChannel->type->value;
        $this->target = $policy->notificationChannel->dsn;
    }

    /*
    #[LiveAction]
    public function save(): Response
    {
        // To be implemented once UpdateEscalationPolicyCommand is verified or created.
    }
    */

    public function getPolicy(): ?EscalationPolicy
    {
        return $this->policyRepository->findById($this->id);
    }
}
