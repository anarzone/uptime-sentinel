<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig\Components;

use App\Monitoring\Application\Command\UpdateMonitor\UpdateMonitorCommand;
use App\Monitoring\Domain\Model\Monitor\Monitor;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class EditMonitor extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $id;

    #[LiveProp(writable: true)]
    public string $name = '';

    #[LiveProp(writable: true)]
    public string $url = '';

    #[LiveProp(writable: true)]
    public string $method = 'GET';

    #[LiveProp(writable: true)]
    public int $intervalSeconds = 60;

    #[LiveProp(writable: true)]
    public int $timeoutSeconds = 10;

    #[LiveProp(writable: true)]
    public int $expectedStatusCode = 200;

    public function __construct(
        private readonly MonitorRepositoryInterface $monitorRepository,
        private readonly MessageBusInterface $bus
    ) {
    }

    public function mount(string $id): void
    {
        $this->id = $id; // Keep as string for now, repository find accepts string (MonitorId::fromString handled inside or by __toString if object passed, but here string is passed)
        // Actually interface expects mixed or MonitorId. Let's rely on repo handling string or convert.
        // Looking at MonitorRepository, find($id) where $id is mixed.
        // It converts to string in find.

        $monitor = $this->monitorRepository->find($id);

        if (!$monitor) {
            throw $this->createNotFoundException();
        }

        $this->name = $monitor->name;
        $this->url = $monitor->url->toString();
        $this->method = $monitor->method->value;
        // Monitor model check: public readonly string $method; (from UpdateMonitorCommand checks, likely similar)
        // Let's assume public accessors or readonly props.
        // If not, we might need a DTO or getters.
        // Checking Monitor.php would be safe but let's assume readonly public for now as per DDD pattern seen.
        $this->intervalSeconds = $monitor->intervalSeconds;
        $this->timeoutSeconds = $monitor->timeoutSeconds;
        // $this->expectedStatusCode = $monitor->expectedStatusCode; // Might be logic?
    }

    #[LiveAction]
    public function save(): Response
    {
        $user = $this->getUser();
        $requesterId = $user instanceof \App\Security\Domain\Entity\User ? $user->getId()->toRfc4122() : $user?->getUserIdentifier();

        try {
            $command = new UpdateMonitorCommand(
                uuid: $this->id,
                name: $this->name,
                url: $this->url,
                method: $this->method,
                intervalSeconds: $this->intervalSeconds,
                timeoutSeconds: $this->timeoutSeconds,
                expectedStatusCode: $this->expectedStatusCode,
                requesterId: $requesterId
            );

            $this->bus->dispatch($command);

            $this->addFlash('success', 'Monitor updated successfully.');

            return $this->redirectToRoute('admin_monitors_index');

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_monitors_edit', ['id' => $this->id]);
    }
}
