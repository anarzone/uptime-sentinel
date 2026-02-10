<?php

declare(strict_types=1);

namespace App\Tests\Unit\Monitoring\Application\Command\EscalationPolicy;

use App\Monitoring\Application\Command\EscalationPolicy\CreateEscalationPolicyCommand;
use App\Monitoring\Application\Command\EscalationPolicy\CreateEscalationPolicyHandler;
use App\Monitoring\Application\Service\MonitorAuthorizationService;
use App\Monitoring\Domain\Model\Alert\EscalationPolicy;
use App\Monitoring\Domain\Model\Notification\NotificationChannel;
use App\Monitoring\Domain\Repository\EscalationPolicyRepositoryInterface;
use App\Monitoring\Domain\Repository\MonitorRepositoryInterface;
use App\Monitoring\Domain\Repository\NotificationChannelRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

use App\Tests\Mother\MonitorMother;
use App\Monitoring\Domain\ValueObject\OwnerId;

class CreateEscalationPolicyHandlerTest extends TestCase
{
    private MonitorRepositoryInterface&MockObject $monitorRepository;
    private NotificationChannelRepositoryInterface&MockObject $notificationChannelRepository;
    private EscalationPolicyRepositoryInterface&MockObject $escalationPolicyRepository;
    private MonitorAuthorizationService $authorizationService;
    private Security&MockObject $security;
    private CreateEscalationPolicyHandler $handler;

    protected function setUp(): void
    {
        $this->monitorRepository = $this->createMock(MonitorRepositoryInterface::class);
        $this->notificationChannelRepository = $this->createMock(NotificationChannelRepositoryInterface::class);
        $this->escalationPolicyRepository = $this->createMock(EscalationPolicyRepositoryInterface::class);
        $this->authorizationService = new MonitorAuthorizationService();
        $this->security = $this->createMock(Security::class);

        $this->handler = new CreateEscalationPolicyHandler(
            $this->monitorRepository,
            $this->notificationChannelRepository,
            $this->escalationPolicyRepository,
            $this->authorizationService,
            $this->security
        );
    }

    public function test_it_uses_existing_channel_if_found(): void
    {
        $requesterId = Uuid::v7()->toRfc4122();
        $command = new CreateEscalationPolicyCommand(
            id: Uuid::v7()->toRfc4122(),
            monitorId: Uuid::v7()->toRfc4122(),
            level: 1,
            consecutiveFailures: 3,
            channel: 'email',
            target: 'existing@example.com',
            requesterId: $requesterId
        );

        $monitor = MonitorMother::create(ownerId: OwnerId::fromString($requesterId));
        $this->monitorRepository->expects($this->once())
            ->method('findById')
            ->willReturn($monitor);

        $existingChannel = NotificationChannel::create(
            name: 'existing',
            type: \App\Monitoring\Domain\Model\Notification\NotificationChannelType::EMAIL,
            dsn: 'existing@example.com',
            ownerId: OwnerId::fromString($requesterId)
        );

        $this->notificationChannelRepository->expects($this->once())
            ->method('findByTypeAndTarget')
            ->willReturn($existingChannel);

        // Expect save NOT to be called for channel
        $this->notificationChannelRepository->expects($this->never())
            ->method('save');

        // Expect policy to be saved
        $this->escalationPolicyRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(EscalationPolicy::class));

        $this->handler->__invoke($command);
    }

    public function test_it_auto_creates_channel_if_missing(): void
    {
        $requesterId = Uuid::v7()->toRfc4122();
        $command = new CreateEscalationPolicyCommand(
            id: Uuid::v7()->toRfc4122(),
            monitorId: Uuid::v7()->toRfc4122(),
            level: 1,
            consecutiveFailures: 3,
            channel: 'email',
            target: 'new@example.com',
            requesterId: $requesterId
        );

        $monitor = MonitorMother::create(ownerId: OwnerId::fromString($requesterId));
        $this->monitorRepository->expects($this->once())
            ->method('findById')
            ->willReturn($monitor);

        // Simulate channel not found
        $this->notificationChannelRepository->expects($this->once())
            ->method('findByTypeAndTarget')
            ->willReturn(null);

        // Expect save to be called for the new channel
        $this->notificationChannelRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (NotificationChannel $channel) use ($command, $requesterId) {
                return $channel->type->value === $command->channel
                    && $channel->dsn === $command->target
                    && $channel->ownerId === $requesterId;
            }));

        // Expect policy to be saved
        $this->escalationPolicyRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(EscalationPolicy::class));

        $this->handler->__invoke($command);
    }
}
