<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\UuidV7;

/**
 * Seed default notification templates.
 */
final class Version20260125000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed default notification templates';
    }

    public function up(Schema $schema): void
    {
        $templates = [
            [
                'name' => 'Default Email Failure',
                'channel' => 'email',
                'event_type' => 'failure',
                'subject' => '🚨 Alert: {{monitorName}} is DOWN',
                'body' => '<h1>🚨 Monitor Alert</h1><p><strong>Monitor:</strong> {{monitorName}}</p><p><strong>URL:</strong> {{url}}</p><p><strong>Status:</strong> {{healthStatus}}</p><p><strong>Consecutive Failures:</strong> {{consecutiveFailures}}</p><p><strong>Last Checked:</strong> {{lastCheckedAt}}</p><hr><p><em>This is an automated message from UptimeSentinel</em></p>',
            ],
            [
                'name' => 'Default Email Recovery',
                'channel' => 'email',
                'event_type' => 'recovery',
                'subject' => '✅ Recovery: {{monitorName}} is UP',
                'body' => '<h1>✅ Monitor Recovered</h1><p><strong>Monitor:</strong> {{monitorName}}</p><p><strong>URL:</strong> {{url}}</p><p><strong>Status:</strong> The monitor has recovered and is now UP</p><p><strong>Recovered At:</strong> {{lastCheckedAt}}</p><hr><p><em>This is an automated message from UptimeSentinel</em></p>',
            ],
            [
                'name' => 'Default Slack Failure',
                'channel' => 'slack',
                'event_type' => 'failure',
                'subject' => null,
                'body' => ':x: *{{monitorName}} is DOWN* • URL: {{url}} • Status: {{healthStatus}} • Failures: {{consecutiveFailures}} • Last Checked: {{lastCheckedAt}}',
            ],
            [
                'name' => 'Default Slack Recovery',
                'channel' => 'slack',
                'event_type' => 'recovery',
                'subject' => null,
                'body' => ':white_check_mark: *{{monitorName}} has recovered* • URL: {{url}} • The monitor is now UP • Recovered at: {{lastCheckedAt}}',
            ],
            [
                'name' => 'Default Slack Escalation',
                'channel' => 'slack',
                'event_type' => 'escalation',
                'subject' => null,
                'body' => ':rotating_light: *ESCALATION: {{monitorName}}* • URL: {{url}} • Consecutive failures: {{consecutiveFailures}} • Last checked: {{lastCheckedAt}}',
            ],
            [
                'name' => 'Default Webhook Failure',
                'channel' => 'webhook',
                'event_type' => 'failure',
                'subject' => null,
                'body' => 'Monitor {{monitorName}} is DOWN ({{url}}). Consecutive failures: {{consecutiveFailures}}',
            ],
        ];

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($templates as $tpl) {
            $this->addSql(
                'INSERT INTO notification_templates (id, name, channel, event_type, subject_template, body_template, is_default, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    (new UuidV7())->toRfc4122(),
                    $tpl['name'],
                    $tpl['channel'],
                    $tpl['event_type'],
                    $tpl['subject'],
                    $tpl['body'],
                    1,
                    $now,
                    $now,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM notification_templates WHERE is_default = 1');
    }
}
