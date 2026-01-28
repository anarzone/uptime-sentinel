<?php

declare(strict_types=1);

namespace App\Monitoring\Domain\Model\Alert;

use App\Monitoring\Domain\Model\Notification\NotificationChannelType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Notification template for customizable alert messages.
 *
 * Supports variable substitution using {{variableName}} syntax.
 */
#[ORM\Entity(repositoryClass: \App\Monitoring\Infrastructure\Persistence\NotificationTemplateRepository::class)]
#[ORM\Table(name: 'notification_templates')]
#[ORM\UniqueConstraint(name: 'unique_name', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'unique_channel_event', columns: ['channel', 'event_type', 'is_default'])]
final class NotificationTemplate
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    public readonly string $id;

    #[ORM\Column(type: Types::STRING, length: 100)]
    public private(set) string $name;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: NotificationChannelType::class)]
    public readonly NotificationChannelType $channel;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: NotificationEventType::class)]
    public readonly NotificationEventType $eventType;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $subjectTemplate;

    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $bodyTemplate;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public private(set) bool $isDefault = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $name,
        NotificationChannelType $channel,
        NotificationEventType $eventType,
        ?string $subjectTemplate,
        string $bodyTemplate,
        bool $isDefault = false,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->channel = $channel;
        $this->eventType = $eventType;
        $this->subjectTemplate = $subjectTemplate;
        $this->bodyTemplate = $bodyTemplate;
        $this->isDefault = $isDefault;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        string $name,
        NotificationChannelType $channel,
        NotificationEventType $eventType,
        ?string $subjectTemplate,
        string $bodyTemplate,
        bool $isDefault = false,
    ): self {
        $uuid = new \Symfony\Component\Uid\UuidV7();

        return new self(
            $uuid->toRfc4122(),
            $name,
            $channel,
            $eventType,
            $subjectTemplate,
            $bodyTemplate,
            $isDefault,
        );
    }

    /**
     * Render the template with the provided variables.
     *
     * @param array<string, string|int|float> $variables Template variables
     *
     * @return NotificationRendered Rendered notification with subject and body
     */
    public function render(array $variables): NotificationRendered
    {
        $subject = $this->subjectTemplate !== null
            ? $this->renderTemplate($this->subjectTemplate, $variables)
            : null;

        $body = $this->renderTemplate($this->bodyTemplate, $variables);

        return new NotificationRendered($subject, $body);
    }

    /**
     * Replace template variables with their values.
     *
     * Supports both {{variable}} and {$variable} syntax.
     */
    private function renderTemplate(string $template, array $variables): string
    {
        $result = $template;

        foreach ($variables as $key => $value) {
            $search = ['{{'.$key.'}}', '{$'.$key.'}'];
            $result = str_replace($search, (string) $value, $result);
        }

        return $result;
    }

    public function updateTemplates(?string $subjectTemplate, string $bodyTemplate): void
    {
        $this->subjectTemplate = $subjectTemplate;
        $this->bodyTemplate = $bodyTemplate;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsDefault(): void
    {
        $this->isDefault = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsNotDefault(): void
    {
        $this->isDefault = false;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
