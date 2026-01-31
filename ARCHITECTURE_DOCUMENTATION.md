# UptimeSentinel - Complete Architecture Documentation

## Executive Summary

**UptimeSentinel** is a distributed uptime monitoring system designed to handle **10,000+ concurrent checks per minute** using a hybrid modular monolith architecture. This document provides a complete technical handover for all three core subsystems: Monitoring, Notifications, and Telemetry.

---

## System Architecture Overview

### Technology Stack
- **Language**: PHP 8.4 (strict types, readonly properties, DDD patterns)
- **Framework**: Symfony 8 (Messenger for async queues, Scheduler for cron)
- **Database**: MySQL 8.0 (partitioning-ready for 10M+ rows/month)
- **Queue**: RabbitMQ (Thundering Herd prevention)
- **Cache/Buffer**: Redis (write buffering, rate limiting, state tracking)
- **Deployment**: Docker Compose (Phase 1), Kubernetes (Phase 2)

### Service Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                     UptimeSentinel System                    │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Web API    │  │  Scheduler   │  │    Worker    │      │
│  │              │  │              │  │              │      │
│  │ - REST API   │  │ - Cron Job   │  │ - Queue      │      │
│  │ - Controllers│  │ - Dispatcher │  │ - Handlers   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                               │
└─────────────────────────────────────────────────────────────┘
          │                  │                  │
          ▼                  ▼                  ▼
    ┌─────────┐        ┌─────────┐        ┌─────────┐
    │  MySQL  │        │RabbitMQ │        │  Redis  │
    └─────────┘        └─────────┘        └─────────┘
```

---

## 1. MONITORING SUBSYSTEM

### 1.1 Purpose
Manages the complete lifecycle of URL monitors, from creation to execution, handling scheduling, distributed checking, and state management.

### 1.2 Domain Model

#### Monitor Entity
**File**: `src/Monitoring/Domain/Model/Monitor/Monitor.php`

```php
final class Monitor
{
    private readonly MonitorId $id;              // UUIDv7 identifier
    private readonly Url $url;                    // Value object (validated URL)
    private readonly HttpMethod $httpMethod;       // GET, POST, HEAD, PUT
    private readonly int $intervalSeconds;        // Check frequency
    private readonly int $timeoutMs;              // Request timeout
    private string $name;                         // Human-readable name
    private MonitorStatus $status;                // ACTIVE, PAUSED, DISABLED
    private MonitorHealth $health;                // UP, DOWN, UNKNOWN
    private int $consecutiveFailures;             // For escalation logic
    private \DateTimeImmutable $nextCheckAt;      // Scheduling timestamp

    // Key business methods
    public function isDue(\DateTimeImmutable $now): bool;
    public function markChecked(CheckResult $result): void;
    public function updateConfiguration(array $config): void;
    public function pause(): void;
    public function resume(): void;
}
```

**Key Design Decisions**:
- **Readonly properties**: Immutable ID and URL prevent accidental modification
- **Value objects**: `Url`, `HttpMethod` encapsulate validation logic
- **Self-state transitions**: `pause()`, `resume()`, `markChecked()` maintain invariants
- **Scheduling logic**: `nextCheckAt` computed from `intervalSeconds`

### 1.3 Command Handlers

#### CreateMonitorHandler
**Files**:
- Command: `src/Monitoring/Application/Command/CreateMonitor/CreateMonitorCommand.php`
- Handler: `src/Monitoring/Application/Command/CreateMonitor/CreateMonitorHandler.php`

**Responsibilities**:
```php
public function __invoke(CreateMonitorCommand $command): void
{
    // 1. Generate UUIDv7 ID
    $monitorId = MonitorId::generate();

    // 2. Create Monitor entity with initial nextCheckAt
    $monitor = new Monitor(
        id: $monitorId,
        url: new Url($command->url),
        httpMethod: HttpMethod::from($command->httpMethod),
        intervalSeconds: $command->intervalSeconds,
        timeoutMs: $command->timeoutMs,
        name: $command->name,
        nextCheckAt: new \DateTimeImmutable('+30 seconds') // First check
    );

    // 3. Persist to database
    $this->repository->save($monitor);
}
```

**Flow**: API → DTO → Command → Handler → Repository → Database

#### UpdateMonitorHandler
**Files**:
- Command: `src/Monitoring/Application/Command/UpdateMonitor/UpdateMonitorCommand.php`
- Handler: `src/Monitoring/Application/Command/UpdateMonitor/UpdateMonitorHandler.php`

**Responsibilities**:
```php
public function __invoke(UpdateMonitorCommand $command): void
{
    // 1. Fetch existing monitor
    $monitor = $this->repository->get($command->id);

    // 2. Delegate to entity (domain logic)
    $monitor->updateConfiguration([
        'name' => $command->name,
        'url' => $command->url,
        'interval' => $command->intervalSeconds,
        'timeout' => $command->timeoutMs,
    ]);

    // 3. Persist changes
    $this->repository->save($monitor);
}
```

#### CheckMonitorHandler (The Core Worker)
**File**: `src/Monitoring/Application/Command/CheckMonitor/CheckMonitorHandler.php`

**Critical Component**: This is where the actual monitoring happens.

```php
public function __invoke(CheckMonitorCommand $command): void
{
    // STEP 1: Fetch monitor
    $monitor = $this->monitorRepository->get($command->monitorId);

    // STEP 2: Guard clause - skip if not active
    if (!$monitor->isActive()) {
        return; // Monitor was paused/disabled after dispatch
    }

    // STEP 3: Perform HTTP check (async, non-blocking)
    $result = $this->urlChecker->check(
        $monitor->getUrl(),
        $monitor->getHttpMethod(),
        $monitor->getTimeoutMs()
    );

    // STEP 4: Buffer result for telemetry (Redis)
    $this->telemetryBuffer->push($result);

    // STEP 5: Update monitor state
    $previousHealth = $monitor->getHealth();
    $monitor->markChecked($result);

    // STEP 6: Trigger notifications if state changed
    if ($previousHealth !== $monitor->getHealth()) {
        $this->alertNotificationService->checkAndNotify($monitor);
    }

    // STEP 7: Persist updated monitor
    $this->monitorRepository->save($monitor);
}
```

**Key Points**:
- **Idempotent**: Safe to run multiple times
- **Race condition handling**: Checks if monitor is still active
- **Separation of concerns**: HTTP checking, buffering, state updates, notifications
- **Transaction boundaries**: Single database write at the end

### 1.4 Scheduler and Dispatcher (Thundering Herd Prevention)

#### The Problem
Running 10,000 monitor checks in a loop every 60 seconds causes:
- Database timeouts (too many connections)
- Network congestion
- Uneven load distribution

#### The Solution: Dispatcher Pattern

**File**: `src/Monitoring/Domain/Service/MonitorDispatcher.php`

```php
public function dispatchDueMonitors(): int
{
    // STEP 1: Find all due monitors in ONE query
    $dueMonitors = $this->monitorRepository->findDueMonitors(
        new \DateTimeImmutable('now')
    );

    // STEP 2: Chunk into batches of 50
    $batches = array_chunk($dueMonitors, 50);

    // STEP 3: Dispatch batches to RabbitMQ
    foreach ($batches as $batch) {
        $this->messageBus->dispatch(
            new CheckMonitorBatchCommand($batch)
        );
    }

    return count($dueMonitors);
}
```

**Why Batching?**
- Reduces RabbitMQ queue size (200 messages vs 10,000)
- Better throughput (bulk processing)
- Prevents memory overload

#### The Scheduler Component

**File**: `src/Monitoring/Infrastructure/Scheduler/MonitoringScheduler.php`

```php
#[AsSchedule('monitoring')]
class MonitoringScheduler implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return new Schedule()
            ->add(
                RecurringMessage::cron('* * * * *', new DispatchMonitorsMessage())
            );
    }
}
```

**Flow**:
```
Cron (every minute)
  ↓
DispatchMonitorsHandler
  ↓
MonitorDispatcher.findDueMonitors()
  ↓
Chunk into batches of 50
  ↓
CheckMonitorBatchCommand → RabbitMQ
  ↓
Worker consumes batches
  ↓
CheckMonitorHandler for each monitor
```

### 1.5 Batch Processing

**File**: `src/Monitoring/Application/Command/CheckMonitorBatch/CheckMonitorBatchHandler.php`

```php
public function __invoke(CheckMonitorBatchCommand $command): void
{
    $monitorIds = $command->monitorIds;

    // STEP 1: Fetch ALL monitors in ONE query (N+1 prevention)
    $monitors = $this->monitorRepository->findByIds($monitorIds);

    // STEP 2: Concurrent checking
    $results = $this->urlChecker->checkBatch($monitors);

    // STEP 3: Stream results to Redis buffer
    foreach ($results as $result) {
        $this->telemetryBuffer->push($result);
    }

    // STEP 4: Update all monitor states
    foreach ($monitors as $monitor) {
        $previousHealth = $monitor->getHealth();
        $monitor->markChecked($results[$monitor->getId()]);

        if ($previousHealth !== $monitor->getHealth()) {
            $this->alertNotificationService->checkAndNotify($monitor);
        }
    }

    // STEP 5: Single transaction flush
    $this->monitorRepository->flush();
}
```

**Performance Optimizations**:
- **Single query**: `findByIds()` uses `WHERE IN (...)`
- **Concurrent HTTP**: Guzzle async requests
- **Redis streaming**: Fast writes, no database I/O
- **Bulk transaction**: One commit for all updates

### 1.6 HTTP Checking Implementation

**File**: `src/Monitoring/Infrastructure/Http/SymfonyHttpUrlChecker.php`

```php
public function check(Url $url, HttpMethod $method, int $timeoutMs): CheckResult
{
    $startTime = microtime(true);

    try {
        $response = $this->client->request(
            $method->value,
            $url->toString(),
            ['timeout' => $timeoutMs / 1000] // Convert to seconds
        );

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        return new CheckResult(
            statusCode: $response->getStatusCode(),
            latencyMs: $latencyMs,
            isSuccessful: $response->isOk(),
            error: null
        );
    } catch (\Exception $e) {
        return new CheckResult(
            statusCode: 0,
            latencyMs: 0,
            isSuccessful: false,
            error: $e->getMessage()
        );
    }
}
```

**Key Features**:
- Precise latency measurement (microtime)
- Timeout handling (prevents hanging)
- Error capture (network failures, DNS issues)

### 1.7 Complete Monitoring Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    MONITORING DATA FLOW                          │
└─────────────────────────────────────────────────────────────────┘

1. API REQUEST
   POST /api/monitors
   {
     "url": "https://example.com",
     "interval": 60,
     "timeout": 5000
   }
   ↓
2. CreateMonitorHandler
   - Generates UUIDv7
   - Creates Monitor entity
   - Sets nextCheckAt = now + 30s (first check)
   ↓
3. DATABASE
   INSERT INTO monitors (...)
   ↓
4. SCHEDULER (every minute via cron)
   ├─ DispatchMonitorsHandler
   ├─ MonitorDispatcher.findDueMonitors()
   │  WHERE next_check_at <= NOW()
   │  ORDER BY next_check_at ASC
   ↓
5. BATCHING
   - Chunk IDs into groups of 50
   - Dispatch CheckMonitorBatchCommand to RabbitMQ
   ↓
6. RABBITMQ QUEUE
   Queue: async
   Messages: CheckMonitorBatchCommand[monitorIds[50]]
   ↓
7. WORKER CONSUMPTION
   CheckMonitorBatchHandler
   ├─ Fetch all 50 monitors (1 query)
   ├─ Concurrent HTTP checks (async)
   ├─ Results → Redis buffer (lpush)
   ├─ Update monitor states
   ├─ Trigger alerts if needed
   └─ Flush transaction (1 write)
   ↓
8. NEXT CHECK SCHEDULED
   monitor.nextCheckAt = NOW() + monitor.intervalSeconds
   ↓
   [REPEATS EVERY MINUTE]
```

---

## 2. NOTIFICATIONS SUBSYSTEM

### 2.1 Purpose
Manages alert rules, escalation policies, and multi-channel notifications with rate limiting and template rendering.

### 2.2 Domain Models

#### AlertRule Entity
**File**: `src/Monitoring/Domain/Model/Alert/AlertRule.php`

```php
final class AlertRule
{
    private readonly AlertRuleId $id;
    private readonly MonitorId $monitorId;
    private readonly NotificationChannel $channel;     // Email, Slack, Webhook
    private readonly NotificationType $type;           // FAILURE, RECOVERY, BOTH
    private readonly int $failureThreshold;            // Consecutive failures needed
    private readonly \DateInterval $cooldownInterval;  // Rate limiting duration
    private bool $isEnabled;

    public function shouldTrigger(Monitor $monitor): bool
    {
        if (!$this->isEnabled) return false;

        $matchesType = match($this->type) {
            NotificationType::FAILURE => $monitor->isDown(),
            NotificationType::RECOVERY => $monitor->isUp() && $monitor->hadPreviousFailure(),
            NotificationType::BOTH => true,
        };

        $meetsThreshold = $monitor->getConsecutiveFailures() >= $this->failureThreshold;

        return $matchesType && $meetsThreshold;
    }
}
```

**Key Features**:
- **Threshold-based**: Only triggers after X consecutive failures
- **Type filtering**: FAILURE (down), RECOVERY (up), BOTH
- **Rate limiting**: Cooldown prevents spam (default 1 hour)
- **Per-monitor**: Each monitor has its own rules

#### EscalationPolicy Entity
**File**: `src/Monitoring/Domain/Model/Alert/EscalationPolicy.php`

```php
final class EscalationPolicy
{
    private readonly EscalationPolicyId $id;
    private readonly ?MonitorId $monitorId;          // NULL = global policy
    private readonly int $level;                     // 1, 2, 3, ... (escalation order)
    private readonly int $consecutiveFailures;       // Exact failure count for this level
    private readonly NotificationChannel $channel;
    private bool $isEnabled;

    public function matches(Monitor $monitor): bool
    {
        // Global policies match all monitors
        if ($this->monitorId === null) {
            return $this->isEnabled;
        }

        // Monitor-specific policies
        return $this->isEnabled && $this->monitorId->equals($monitor->getId());
    }

    public function shouldTrigger(Monitor $monitor): bool
    {
        return $this->matches($monitor)
            && $monitor->getConsecutiveFailures() === $this->consecutiveFailures;
    }
}
```

**Escalation Example**:
```
Level 1: 5 failures → Email to on-call@company.com
Level 2: 10 failures → Slack #incident-alerts
Level 3: 15 failures → Webhook to PagerDuty (create incident)
```

**Key Features**:
- **Multi-level**: Supports unlimited escalation levels
- **Global or specific**: `monitorId = null` applies to all monitors
- **Exact matching**: Only triggers at exact failure count (not subsequent checks)
- **Priority**: Monitor-specific policies override global ones

#### NotificationChannel Entity
**File**: `src/Monitoring/Domain/Model/Notification/NotificationChannel.php`

```php
final class NotificationChannel
{
    private readonly NotificationChannelId $id;
    private string $name;                    // Human-readable name
    private readonly NotificationChannelType $type;  // EMAIL, SLACK, WEBHOOK
    private string $dsn;                     // Symfony Notifier DSN
    private bool $isEnabled;

    // Examples:
    // Email: "mailto://smtp?host=smtp.gmail.com&port=587"
    // Slack: "slack://xoxb-token@default?channel=alerts"
    // Webhook: "webhook://https://api.pagerduty.com/incidents"
}
```

**Channel Types**:
- **EMAIL**: SMTP or mailgun://, sendgrid://
- **SLACK**: slack://token@default?channel=general
- **WEBHOOK**: webhook://https://api.example.com/events

### 2.3 AlertNotificationService (The Orchestrator)

**File**: `src/Monitoring/Application/Service/AlertNotificationService.php`

This is the **CORE** of the notification system.

```php
public function checkAndNotify(Monitor $monitor): void
{
    $previousHealth = $this->monitorStateRepository->get($monitor->getId());

    // FAILURE NOTIFICATIONS
    if ($monitor->isDown() && $previousHealth !== MonitorHealth::DOWN) {
        $this->handleFailureNotifications($monitor);
    }

    // RECOVERY NOTIFICATIONS
    if ($monitor->isUp() && $previousHealth === MonitorHealth::DOWN) {
        $this->handleRecoveryNotifications($monitor);
    }

    // ESCALATION NOTIFICATIONS (always check)
    $this->handleEscalationNotifications($monitor);

    // Update stored state
    $this->monitorStateRepository->save($monitor->getId(), $monitor->getHealth());
}
```

#### handleFailureNotifications()

```php
private function handleFailureNotifications(Monitor $monitor): void
{
    $rules = $this->alertRuleRepository->findByMonitor($monitor->getId());

    foreach ($rules as $rule) {
        if (!$rule->shouldTrigger($monitor)) {
            continue;
        }

        // Rate limiting check
        if (!$this->canSendNotification($rule, $monitor)) {
            $this->logger->info('Notification rate limited', [
                'rule' => $rule->getId()->toString(),
                'monitor' => $monitor->getId()->toString()
            ]);
            continue;
        }

        $this->dispatchNotification(
            channel: $rule->getChannel(),
            monitor: $monitor,
            eventType: NotificationEventType::FAILURE
        );
    }
}
```

#### handleRecoveryNotifications()

```php
private function handleRecoveryNotifications(Monitor $monitor): void
{
    $rules = $this->alertRuleRepository->findByMonitor($monitor->getId());

    foreach ($rules as $rule) {
        $type = $rule->getType();

        // Only send if rule is configured for RECOVERY or BOTH
        if ($type !== NotificationType::RECOVERY && $type !== NotificationType::BOTH) {
            continue;
        }

        if (!$this->canSendNotification($rule, $monitor)) {
            continue;
        }

        $this->dispatchNotification(
            channel: $rule->getChannel(),
            monitor: $monitor,
            eventType: NotificationEventType::RECOVERY
        );
    }
}
```

#### handleEscalationNotifications()

```php
private function handleEscalationNotifications(Monitor $monitor): void
{
    // Fetch all applicable policies (monitor-specific + global)
    $policies = $this->escalationPolicyRepository->findApplicableForMonitor($monitor->getId());

    // Sort by level (1 → 2 → 3)
    usort($policies, fn($a, $b) => $a->getLevel() <=> $b->getLevel());

    foreach ($policies as $policy) {
        if (!$policy->shouldTrigger($monitor)) {
            continue;
        }

        // Check if we already sent this exact level
        $stateKey = sprintf('escalation:%s:%d', $monitor->getId()->toString(), $policy->getLevel());
        if ($this->cache->getItem($stateKey)->isHit()) {
            continue; // Already notified for this level
        }

        $this->dispatchNotification(
            channel: $policy->getChannel(),
            monitor: $monitor,
            eventType: NotificationEventType::ESCALATION,
            escalationLevel: $policy->getLevel()
        );

        // Mark as sent
        $item = $this->cache->getItem($stateKey);
        $item->set(true);
        $item->expiresAfter(3600 * 24); // Remember for 24 hours
        $this->cache->save($item);
    }
}
```

**Key Escalation Logic**:
- **Exact matching**: Level 2 triggers at exactly 10 failures, not 11, 12, etc.
- **One-time per level**: Uses cache to prevent duplicate sends
- **Level ordering**: Processes policies in order (1 → 2 → 3)

### 2.4 Rate Limiting

**Implementation**: Symfony Rate Limiter + Redis backend

```php
private function canSendNotification(AlertRule $rule, Monitor $monitor): bool
{
    $limiter = $this->limiterFactory->create(
        identifier: sprintf('notification:rule:%s:%s', $monitor->getId(), $rule->getId()),
        factory: new FixedWindowLimiter(),
        configuration: [
            'interval' => $rule->getCooldownInterval(),  // e.g., 'PT1H' (1 hour)
            'limit' => 1  // Only 1 notification per interval
        ]
    );

    $limit = $limiter->consume(1, $this->clientIp);

    return !$limit->isAccepted();
}
```

**Example**:
```
Alert Rule: failureThreshold = 3, cooldown = PT1H

Timeline:
- 10:00:00 - Monitor fails (1/3) - No notification
- 10:00:30 - Monitor fails (2/3) - No notification
- 10:01:00 - Monitor fails (3/3) - ✅ NOTIFICATION SENT (rate limit reset)
- 10:01:30 - Monitor fails (4/3) - ❌ BLOCKED by rate limit
- 10:02:00 - Monitor fails (5/3) - ❌ BLOCKED by rate limit
- 11:01:00 - Monitor fails (6/3) - ✅ NOTIFICATION SENT (1 hour passed)
```

### 2.5 Notification Dispatch

```php
private function dispatchNotification(
    NotificationChannel $channel,
    Monitor $monitor,
    NotificationEventType $eventType,
    int $escalationLevel = null
): void {
    try {
        $subject = $this->generateSubject($monitor, $eventType, $escalationLevel);
        $message = $this->generateMessage($monitor, $eventType, $escalationLevel);

        match($channel->getType()) {
            NotificationChannelType::EMAIL => $this->sendEmail(
                $channel->getDsn(),
                $subject,
                $message
            ),
            NotificationChannelType::SLACK => $this->sendSlack(
                $channel->getDsn(),
                $message
            ),
            NotificationChannelType::WEBHOOK => $this->sendWebhook(
                $channel->getDsn(),
                $message
            ),
        };

        // Reset rate limit on successful send
        $this->resetRateLimit($channel, $monitor);
    }
    catch (\Exception $e) {
        $this->logger->error('Failed to send notification', [
            'channel' => $channel->getId()->toString(),
            'monitor' => $monitor->getId()->toString(),
            'error' => $e->getMessage()
        ]);
    }
}
```

#### Email Sending
```php
private function sendEmail(string $dsn, string $subject, string $message): void
{
    $email = (new Email())
        ->subject($subject)
        ->to($this->extractEmailFromDsn($dsn))
        ->text($message);

    $this->mailer->send($email);
}
```

#### Slack Sending
```php
private function sendSlack(string $dsn, string $message): void
{
    $transport = Transport::fromDsn($dsn);
    $chatMessage = new ChatMessage($message);

    $transport->send($chatMessage);
}
```

#### Webhook Sending
```php
private function sendWebhook(string $dsn, string $message): void
{
    $transport = Transport::fromDsn($dsn);

    $webhook = new WebhookMessage([
        'text' => $message,
        'timestamp' => time(),
        'monitor_id' => $monitor->getId()->toString(),
        'status' => $monitor->getHealth()->value,
    ]);

    $transport->send($webhook);
}
```

### 2.6 Command Handlers (Alert Rules)

#### CreateAlertRuleHandler
**Files**:
- Command: `src/Monitoring/Application/Command/AlertRule/CreateAlertRuleCommand.php`
- Handler: `src/Monitoring/Application/Command/AlertRule/CreateAlertRuleHandler.php`

```php
public function __invoke(CreateAlertRuleCommand $command): void
{
    $rule = new AlertRule(
        id: AlertRuleId::generate(),
        monitorId: new MonitorId($command->monitorId),
        channel: $this->getOrCreateChannel($command->channelDsn),
        type: NotificationType::from($command->type),
        failureThreshold: $command->failureThreshold,
        cooldownInterval: new \DateInterval($command->cooldownInterval),
        isEnabled: true
    );

    $this->alertRuleRepository->save($rule);
}
```

**Auto-creates channels**: If a channel with the DSN doesn't exist, it creates one.

#### UpdateAlertRuleHandler
```php
public function __invoke(UpdateAlertRuleCommand $command): void
{
    $rule = $this->alertRuleRepository->get($command->id);

    $rule->updateConfiguration(
        channel: $this->getOrCreateChannel($command->channelDsn),
        type: NotificationType::from($command->type),
        failureThreshold: $command->failureThreshold,
        cooldownInterval: new \DateInterval($command->cooldownInterval)
    );

    $this->alertRuleRepository->save($rule);
}
```

### 2.7 Command Handlers (Escalation Policies)

#### CreateEscalationPolicyHandler
**Files**:
- Command: `src/Monitoring/Application/Command/EscalationPolicy/CreateEscalationPolicyCommand.php`
- Handler: `src/Monitoring/Application/Command/EscalationPolicy/CreateEscalationPolicyHandler.php`

```php
public function __invoke(CreateEscalationPolicyCommand $command): void
{
    $policy = new EscalationPolicy(
        id: EscalationPolicyId::generate(),
        monitorId: $command->monitorId ? new MonitorId($command->monitorId) : null,
        level: $command->level,
        consecutiveFailures: $command->consecutiveFailures,
        channel: $this->getOrCreateChannel($command->channelDsn),
        isEnabled: true
    );

    $this->escalationPolicyRepository->save($policy);
}
```

**Monitor-specific vs Global**:
- `monitorId` provided → Only applies to that monitor
- `monitorId` = null → Applies to ALL monitors (global policy)

### 2.8 Controllers

#### AlertRuleController
**File**: `src/Monitoring/Infrastructure/Controller/AlertRuleController.php`

**Endpoints**:
```
POST   /api/alert-rules                          Create new rule
GET    /api/alert-rules/monitor/{monitorId}      List all rules for monitor
GET    /api/alert-rules/{id}                     Get specific rule
PATCH  /api/alert-rules/{id}                     Update rule
DELETE /api/alert-rules/{id}                     Delete rule
PATCH  /api/alert-rules/{id}/enable              Enable rule
PATCH  /api/alert-rules/{id}/disable             Disable rule
```

#### EscalationPolicyController
**File**: `src/Monitoring/Infrastructure/Controller/EscalationPolicyController.php`

**Endpoints**:
```
POST   /api/escalation-policies                  Create policy
GET    /api/escalation-policies/monitor/{id}     List applicable policies
GET    /api/escalation-policies                  List all policies
GET    /api/escalation-policies/{id}             Get specific policy
DELETE /api/escalation-policies/{id}             Delete policy
PATCH  /api/escalation-policies/{id}/enable      Enable policy
PATCH  /api/escalation-policies/{id}/disable     Disable policy
```

### 2.9 Complete Notification Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                  NOTIFICATION DATA FLOW                          │
└─────────────────────────────────────────────────────────────────┘

1. MONITOR CHECK COMPLETES
   CheckMonitorHandler detects health change (UP → DOWN or DOWN → UP)
   ↓
2. AlertNotificationService.checkAndNotify(monitor)
   ├─ Fetch previous health from MonitorStateRepository (Redis)
   ├─ Compare: if ($previous !== $current)
   ↓
3. FAILURE FLOW (if DOWN)
   handleFailureNotifications(monitor)
   ├─ Fetch AlertRules for monitor
   ├─ For each rule:
   │  ├─ Check rule.isEnabled
   │  ├─ Check rule.type matches (FAILURE or BOTH)
   │  ├─ Check rule.threshold <= monitor.consecutiveFailures
   │  ├─ Check rate limiter (Redis: "notification:rule:{id}")
   │  └─ If all pass → dispatchNotification()
   ↓
4. RECOVERY FLOW (if UP)
   handleRecoveryNotifications(monitor)
   ├─ Fetch AlertRules for monitor
   ├─ For each rule:
   │  ├─ Check rule.type matches (RECOVERY or BOTH)
   │  ├─ Check rate limiter
   │  └─ If all pass → dispatchNotification()
   ↓
5. ESCALATION FLOW (always runs)
   handleEscalationNotifications(monitor)
   ├─ Fetch EscalationPolicies (monitor-specific + global)
   ├─ Sort by level (1 → 2 → 3)
   ├─ For each policy:
   │  ├─ Check policy.isEnabled
   │  ├─ Check policy.monitorId matches (or is null)
   │  ├─ Check policy.consecutiveFailures === monitor.consecutiveFailures (exact!)
   │  ├─ Check cache: "escalation:{monitorId}:{level}"
   │  └─ If not already sent → dispatchNotification()
   ↓
6. dispatchNotification(channel, monitor, eventType)
   ├─ Generate subject and message
   ├─ Switch on channel.type:
   │  ├─ EMAIL → Send via Symfony Mailer
   │  ├─ SLACK → Send via Symfony Notifier
   │  └─ WEBHOOK → Send via Symfony Notifier
   ├─ On success → Reset rate limit
   └─ On error → Log exception (don't fail the check)
   ↓
7. Update MonitorStateRepository
   Save current health for next comparison
```

### 2.10 Real-World Example

**Scenario**: Monitor fails repeatedly with escalation

```
Configuration:
- AlertRule: threshold=5, cooldown=1h, type=FAILURE, channel=email
- Escalation Level 1: failures=5, channel=slack
- Escalation Level 2: failures=10, channel=pagerduty
- Escalation Level 3: failures=15, channel=webhook

Timeline:
┌──────────────┬───────────────┬─────────────────────────────────────┐
│ Time         │ Failures      │ Notifications Sent                  │
├──────────────┼───────────────┼─────────────────────────────────────┤
│ 10:00:00     │ 1/5           │ None (below threshold)              │
│ 10:00:30     │ 2/5           │ None (below threshold)              │
│ 10:01:00     │ 3/5           │ None (below threshold)              │
│ 10:01:30     │ 4/5           │ None (below threshold)              │
│ 10:02:00     │ 5/5           │ ✅ Email (AlertRule triggered)      │
│              │               │ ✅ Slack (Escalation Level 1)       │
├──────────────┼───────────────┼─────────────────────────────────────┤
│ 10:02:30     │ 6/5           │ None (rate limited, no exact match) │
│ 10:03:00     │ 7/5           │ None (rate limited, no exact match) │
│ ...          │ ...           │ ... (rate limited for 1 hour)      │
├──────────────┼───────────────┼─────────────────────────────────────┤
│ 10:15:00     │ 10/5          │ ✅ PagerDuty (Escalation Level 2)   │
├──────────────┼───────────────┼─────────────────────────────────────┤
│ 10:30:00     │ 15/5          │ ✅ Webhook (Escalation Level 3)     │
├──────────────┼───────────────┼─────────────────────────────────────┤
│ 11:05:00     │ 20/5          │ ✅ Email (1 hour cooldown passed)   │
└──────────────┴───────────────┴─────────────────────────────────────┘
```

**Key Observations**:
1. **Threshold-based**: First notification at 5 failures
2. **Exact escalation**: Level 2 triggers at exactly 10 failures (not 9, not 11)
3. **Rate limiting**: Email blocked for 1 hour after first send
4. **No escalation spam**: Each level only sends once (tracked in cache)

---

## 3. TELEMETRY SUBSYSTEM

### 3.1 Purpose
Without Partitioning:
- Query: "SELECT AVG(latency) FROM telemetry WHERE checked_at > NOW() - 30 DAY"
- Table scan: 432M rows
- Time: ~10 minutes

With Partitioning:
- Same query scans only 30 partitions
- Rows scanned: 432M / 12 = 36M rows
- Time: ~45 seconds
```

**Write Buffering Impact**:
```
Without Buffering (10K individual inserts):
- Transactions: 10,000 per minute
- Index updates: 10,000 per minute
- Disk I/O: High
- CPU: 80% (database)

With Buffering (10K/minute → 10 bulk inserts/minute):
- Transactions: 10 per minute
- Index updates: 10 per minute
- Disk I/O: Low
- CPU: 20% (database)
```

---

## 4. RABBITMQ INTEGRATION

### 4.1 Configuration

**File**: `config/packages/messenger.yaml`

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    queue_name: async
                    auto_setup: true  # Auto-create queues
        routing:
            'App\Monitoring\Application\Command\CheckMonitor\CheckMonitorBatchCommand': async
            'Symfony\Component\Mailer\SentMessage': async
            'Symfony\Component\Notifier\Message\ChatMessage': async
            'Symfony\Component\Notifier\Message\SmsMessage': async

        failure_transport: doctrine  # Failed messages go to database
```

**Environment Variable**:
```bash
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f
```

### 4.2 Queue Structure

#### Exchanges and Queues
```
Exchange: (default AMQP direct exchange)
    ↓
Queue: async
    ├─ CheckMonitorBatchCommand messages
    ├─ Email messages
    ├─ Chat (Slack) messages
    └─ SMS messages
```

**Auto-Setup**: RabbitMQ automatically creates the `async` queue on first connection.

#### Message Flow
```
1. Producer (Dispatcher)
   $bus->dispatch(new CheckMonitorBatchCommand([...]))
   ↓
2. Messenger Component
   - Serializes message to JSON
   - Adds metadata (timestamp, retries)
   ↓
3. RabbitMQ
   - Message → async queue
   - Persists to disk
   ↓
4. Consumer (Worker)
   $ php bin/console messenger:consume async
   - Receives message
   - Deserializes to CheckMonitorBatchCommand
   - Calls CheckMonitorBatchHandler
   ↓
5. Ack/Nack
   - Success → ACK (remove from queue)
   - Failure → NACK (requeue or move to failed transport)
```

### 4.3 Message Handlers

#### CheckMonitorBatchHandler
**Worker Command**:
```bash
php bin/console messenger:consume async -vv
```

**What it does**:
1. Long-running process (doesn't exit)
2. Polls `async` queue continuously
3. Dispatches message to appropriate handler
4. Handles retries (3 attempts by default)
5. Moves failed messages to `doctrine` transport

#### Console Commands
```bash
# Start worker (runs forever)
php bin/console messenger:consume async

# Consume with limit (exit after N messages)
php bin/console messenger:consume async --limit=100

# Consume with timeout (exit after N seconds)
php bin/console messenger:consume async --time-limit=3600

# Worker with specific receiver
php bin/console messenger:consume async --receiver=async

# Multiple workers (parallel processing)
php bin/console messenger:consume async --workers=4
```

### 4.4 Docker Services

**File**: `compose.yaml`

```yaml
services:
  rabbitmq:
    image: rabbitmq:3-management
    ports:
      - "5672:5672"    # AMQP protocol
      - "15672:15672"  # Management UI
    environment:
      RABBITMQ_DEFAULT_USER: guest
      RABBITMQ_DEFAULT_PASS: guest
    volumes:
      - rabbitmq_data:/var/lib/rabbitmq
    healthcheck:
      test: ["CMD", "rabbitmq-diagnostics", "-q", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  scheduler:
    image: uptime-sentinel:latest
    command: php bin/console messenger:consume scheduler_monitoring
    depends_on:
      rabbitmq:
        condition: service_healthy
    restart: unless-stopped

  worker:
    image: uptime-sentinel:latest
    command: php bin/console messenger:consume async
    depends_on:
      rabbitmq:
        condition: service_healthy
    restart: unless-stopped
```

**Service Architecture**:
- **Scheduler**: Consumes `scheduler_monitoring` queue (cron jobs)
- **Worker**: Consumes `async` queue (monitor checks)
- **Health Checks**: RabbitMQ must be healthy before workers start

### 4.5 Monitoring RabbitMQ

**Management UI**: `http://localhost:15672` (guest/guest)

**Key Metrics**:
- **Queue depth**: Number of messages in `async` queue
- **Message rate**: Messages/second published/consumed
- **Consumer count**: Number of active workers

**CLI Commands**:
```bash
# List queues
docker exec rabbitmq rabbitmqctl list_queues

# Purge queue (emergency use only)
docker exec rabbitmq rabbitmqctl purge_queue async

# Get queue stats
docker exec rabbitmq rabbitmqctl list_queues name messages consumers
```

---

## 5. REDIS INTEGRATION

### 5.1 Configuration

**File**: `config/packages/cache.yaml`

```yaml
framework:
    cache:
        prefix_seed: uptime_sentinel  # Prevents cache key collisions
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'
        pools:
            app:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_URL)%'
            cache.rate_limiter:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_URL)%'
```

**Environment Variable**:
```bash
REDIS_URL=redis://redis:6379
```

### 5.2 Usage Patterns

#### 1. Telemetry Buffer (List)
```php
// Worker writes
$redis->lpush('telemetry_buffer', json_encode($result));

// Ingestor reads
$batch = [];
for ($i = 0; $i < 1000; $i++) {
    $item = $redis->rpop('telemetry_buffer');
    if (!$item) break;
    $batch[] = json_decode($item, true);
}
```

**Why LPUSH/RPOP?**
- **LPUSH** (left push): Fast, O(1) operation
- **RPOP** (right pop): FIFO order (oldest data first)
- **Non-blocking**: Workers don't wait if buffer is empty

#### 2. Monitor State (String)
**File**: `src/Monitoring/Infrastructure/Persistence/MonitorStateRepository.php`

```php
public function save(MonitorId $monitorId, MonitorHealth $health): void
{
    $key = sprintf('monitor_state:%s', $monitorId->toString());
    $this->redis->set($key, $health->value);
}

public function get(MonitorId $monitorId): MonitorHealth
{
    $key = sprintf('monitor_state:%s', $monitorId->toString());
    $value = $this->redis->get($key);
    return MonitorHealth::from($value ?? 'UNKNOWN');
}
```

**Why Redis?**
- Fast lookups (sub-millisecond)
- No database queries for every check
- Survives worker restarts (in-memory but persisted)

#### 3. Rate Limiting (Symfony Cache)
```php
$limiter = $limiterFactory->create(
    identifier: 'notification:rule:{monitorId}:{ruleId}',
    factory: new FixedWindowLimiter(),
    configuration: ['interval' => 'PT1H', 'limit' => 1]
);

$limit = $limiter->consume(1, $clientIp);
if ($limit->isAccepted()) {
    // Send notification
}
```

**Storage**: `cache.rate_limiter` pool (Redis backend)

#### 4. Escalation Tracking (Cache)
```php
// Check if we already sent level 2 for this monitor
$key = sprintf('escalation:%s:%d', $monitorId->toString(), $level);
$item = $this->cache->getItem($key);

if ($item->isHit()) {
    return; // Already sent
}

// Mark as sent
$item->set(true);
$item->expiresAfter(3600 * 24); // 24 hours
$this->cache->save($item);
```

**Why Cache vs Redis Direct?**
- **Auto-serialization**: Handles TTL automatically
- **Symfony integration**: Works with RateLimiter
- **Abstraction**: Easy to swap backends (APCu, Memcached)

### 5.3 Docker Service

**File**: `compose.yaml`

```yaml
redis:
  image: redis:7-alpine
  ports:
    - "6379:6379"
  volumes:
    - redis_data:/data
  command: redis-server --appendonly yes  # AOF persistence
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 10s
    timeout: 5s
    retries: 5
```

**Persistence**: AOF (Append-Only File) for durability

### 5.4 Monitoring Redis

**CLI Commands**:
```bash
# Connect to Redis CLI
docker exec -it redis redis-cli

# Monitor all commands (debugging)
127.0.0.1:6379> MONITOR

# Get buffer size
127.0.0.1:6379> LLEN telemetry_buffer

# Get memory usage
127.0.0.1:6379> INFO memory

# Get all keys with pattern
127.0.0.1:6379> KEYS monitor_state:*

# Flush all data (emergency use only)
127.0.0.1:6379> FLUSHALL
```

**Key Patterns**:
```
telemetry_buffer              → List (telemetry write buffer)
monitor_state:{uuid}          → String (previous health state)
notification:rule:{uuid}:{id} → Rate limiter cache
escalation:{uuid}:{level}     → Escalation tracking cache
```

---

## 6. CROSS-CUTTING CONCERNS

### 6.1 Error Handling Strategy

#### Monitoring Errors
```php
// CheckMonitorHandler
try {
    $result = $this->urlChecker->check(...);
} catch (\Exception $e) {
    // Log error but don't crash worker
    $this->logger->error('Check failed', [
        'monitor_id' => $monitor->getId(),
        'error' => $e->getMessage()
    ]);

    // Create failed CheckResult
    $result = new CheckResult(
        statusCode: 0,
        latencyMs: 0,
        isSuccessful: false,
        error: $e->getMessage()
    );
}
```

**Principle**: Workers must never crash on individual monitor failures.

#### Notification Errors
```php
// AlertNotificationService
try {
    $this->sendEmail(...);
} catch (\Exception $e) {
    $this->logger->error('Notification failed', [
        'channel' => $channel->getId(),
        'error' => $e->getMessage()
    ]);
    // Don't throw - let other notifications continue
}
```

**Principle**: Notification failures should not block monitoring.

#### RabbitMQ Failures
```php
// Failed transport (doctrine)
# Messages that fail 3 times go here
php bin/console messenger:consume failed
```

**Recovery Strategy**:
1. Messages automatically retry (3 attempts)
2. Failed messages go to `doctrine` transport (database)
3. Manual inspection via admin UI
4. Replay failed messages when fixed

### 6.2 Logging Strategy

#### Log Levels
- **DEBUG**: Detailed step-by-step (development only)
- **INFO**: Normal operations (monitor checked, notification sent)
- **WARNING**: Recoverable issues (rate limited, retry attempt)
- **ERROR**: Failures (check failed, notification error)
- **CRITICAL**: System failures (database down, queue unreachable)

#### Structured Logging
```php
$this->logger->info('Monitor checked', [
    'monitor_id' => $monitor->getId()->toString(),
    'status_code' => $result->statusCode,
    'latency_ms' => $result->latencyMs,
    'duration' => $duration->total_seconds,
]);
```

**Benefits**:
- JSON logs for log aggregation (ELK, Loki)
- Queryable by field
- Correlation IDs for request tracing

### 6.3 Security Considerations

#### Input Validation
```php
// Controller
#[Assert\NotBlank]
#[Assert\Url]
public string $url;

#[Assert\Choice(choices: ['GET', 'POST', 'HEAD', 'PUT'])]
public string $httpMethod;
```

#### SQL Injection Prevention
```php
// ALWAYS use parameterized queries
$this->connection->executeStatement(
    'SELECT * FROM monitors WHERE id = ?',
    [$monitorId]  // Auto-escaped
);
```

#### Secrets Management
```bash
# .env (committed, defaults only)
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f

# .env.local (gitignored, actual secrets)
MESSENGER_TRANSPORT_DSN=amqp://user:secure_password@prod-rabbitmq:5672/%2f
```

**Rule**: Never commit secrets to Git.

---

## 7. TESTING STRATEGY

### 7.1 Unit Tests (Domain Layer)

**Example**: Monitor entity state transitions

```php
final class MonitorTest extends TestCase
{
    public function test_can_be_paused(): void
    {
        $monitor = new Monitor(
            id: MonitorId::generate(),
            url: new Url('https://example.com'),
            // ... other params
        );

        $monitor->pause();

        $this->assertEquals(MonitorStatus::PAUSED, $monitor->getStatus());
    }

    public function test_cannot_be_paused_twice(): void
    {
        $monitor = /* ... */
        $monitor->pause();

        $this->expectException(\DomainException::class);
        $monitor->pause();
    }
}
```

**Focus**: Business logic and invariants.

### 7.2 Integration Tests (Application Layer)

**Example**: CreateMonitorHandler

```php
final class CreateMonitorHandlerTest extends KernelTestCase
{
    public function test_creates_monitor_with_next_check_time(): void
    {
        self::bootKernel();

        $handler = self::getContainer()->get(CreateMonitorHandler::class);
        $repository = self::getContainer()->get(MonitorRepositoryInterface::class);

        $command = new CreateMonitorCommand(
            url: 'https://example.com',
            intervalSeconds: 60,
            // ...
        );

        $handler($command);

        $monitor = $repository->findLatest();

        $this->assertNotNull($monitor->getNextCheckAt());
        $this->assertGreaterThan(new \DateTimeImmutable('now'), $monitor->getNextCheckAt());
    }
}
```

**Focus**: Command handlers and repositories.

### 7.3 End-to-End Tests (API Layer)

**Example**: Create monitor via API

```php
final class MonitorApiTest extends ApiTestCase
{
    public function test_can_create_monitor_via_api(): void
    {
        $response = self::createClient()->request('POST', '/api/monitors', [
            'json' => [
                'url' => 'https://example.com',
                'interval_seconds' => 60,
                'timeout_ms' => 5000,
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'url' => 'https://example.com',
            'status' => 'ACTIVE',
        ]);
    }
}
```

**Focus**: HTTP endpoints and integration.

---

## 8. DEPLOYMENT ARCHITECTURE

### 8.1 Phase 1: Docker Compose (Current)

```
┌─────────────────────────────────────────────────────┐
│                  Single Server                      │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐   │
│  │  Web API   │  │ Scheduler  │  │   Worker   │   │
│  │  (PHP-FPM) │  │   (CLI)    │  │   (CLI)    │   │
│  └────────────┘  └────────────┘  └────────────┘   │
│                                                     │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐   │
│  │   MySQL    │  │  RabbitMQ  │  │   Redis    │   │
│  └────────────┘  └────────────┘  └────────────┘   │
└─────────────────────────────────────────────────────┘
```

**Capacity**: ~10,000 monitors, single point of failure

### 8.2 Phase 2: Horizontal Scaling (Planned)

```
┌───────────────────────────────────────────────────────────────┐
│                       Load Balancer                            │
└───────────────────────────────────────────────────────────────┘
         │                 │                 │
         ▼                 ▼                 ▼
    ┌─────────┐       ┌─────────┐       ┌─────────┐
    │ Web API │       │ Web API │       │ Web API │  (3 instances)
    └─────────┘       └─────────┘       └─────────┘
         │                 │                 │
         └─────────────────┴─────────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         ▼                 ▼                 ▼
    ┌─────────┐       ┌─────────┐       ┌─────────┐
    │Scheduler │       │Scheduler │       │Scheduler │ (3 instances)
    └─────────┘       └─────────┘       └─────────┘
         │                 │                 │
         └─────────────────┴─────────────────┘
                           │
    ┌──────────────────────────────────────────────────┐
    │              RabbitMQ Cluster                     │
    │  ┌─────────┐  ┌─────────┐  ┌─────────┐          │
    │  │ Node 1  │  │ Node 2  │  │ Node 3  │ (Mirror) │
    │  └─────────┘  └─────────┘  └─────────┘          │
    └──────────────────────────────────────────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         ▼                 ▼                 ▼
    ┌─────────┐       ┌─────────┐       ┌─────────┐
    │ Worker  │       │ Worker  │       │ Worker  │  (10+ instances)
    └─────────┘       └─────────┘       └─────────┘
         │                 │                 │
         └─────────────────┴─────────────────┘
                           │
    ┌──────────────────────────────────────────────────┐
    │              Redis Cluster                        │
    │  ┌─────────┐  ┌─────────┐  ┌─────────┐          │
    │  │ Master  │  │ Replica │  │ Replica │           │
    │  └─────────┘  └─────────┘  └─────────┘          │
    └──────────────────────────────────────────────────┘
                           │
    ┌──────────────────────────────────────────────────┐
    │              MySQL Primary-Replica               │
    │  ┌─────────┐  ┌─────────┐  ┌─────────┐          │
    │  │ Primary │  │ Replica │  │ Replica │ (Read)   │
    │  └─────────┘  └─────────┘  └─────────┘          │
    └──────────────────────────────────────────────────┘
```

**Capacity**: 100,000+ monitors, HA, auto-scaling

**Scaling Strategy**:
- **Web API**: Scale on CPU/memory (Kubernetes HPA)
- **Scheduler**: 3 instances (leader election via cron)
- **Worker**: Scale on queue depth (auto-scaling based on RabbitMQ queue size)
- **RabbitMQ**: Cluster with mirrored queues
- **Redis**: Cluster with sharding
- **MySQL**: Primary-replica with read splitting

---

## 9. MONITORING & OBSERVABILITY

### 9.1 Application Metrics

**Key Metrics to Track**:
```
- monitor_checks_total{status="success|failure"}
- monitor_check_duration_seconds{p50, p95, p99}
- monitor_queue_depth (RabbitMQ)
- notification_sent_total{channel="email|slack|webhook"}
- notification_rate_limited_total
- telemetry_buffer_size (Redis)
- telemetry_ingestion_batch_size
```

**Tools**:
- Prometheus for metrics collection
- Grafana for dashboards
- Alertmanager for alerting

### 9.2 Distributed Tracing

**Context Propagation**:
```php
// Add trace ID to all logs
$traceId = $this->request->headers->get('X-Trace-Id', Uuid::v4());
$this->logger->info('Processing monitor', [
    'trace_id' => $traceId,
    'monitor_id' => $monitor->getId()
]);
```

**Tools**:
- Jaeger or Zipkin for trace visualization
- OpenTelemetry SDK for instrumentation

### 9.3 Health Checks

**Endpoint**: `GET /health`

```json
{
  "status": "healthy",
  "components": {
    "database": "healthy",
    "rabbitmq": "healthy",
    "redis": "healthy"
  },
  "timestamp": "2026-01-26T18:00:00Z"
}
```

**Implementation**:
```php
#[Route('/health')]
public function health(): Response
{
    $checks = [
        'database' => $this->checkDatabase(),
        'rabbitmq' => $this->checkRabbitMq(),
        'redis' => $this->checkRedis(),
    ];

    $status = in_array(false, $checks) ? 'unhealthy' : 'healthy';

    return $this->json([
        'status' => $status,
        'components' => $checks,
        'timestamp' => new \DateTimeImmutable()
    ], $status === 'healthy' ? 200 : 503);
}
```

---

## 10. QUICK REFERENCE

### 10.1 File Locations

**Monitoring**:
- Entity: `src/Monitoring/Domain/Model/Monitor/Monitor.php`
- Handlers: `src/Monitoring/Application/Command/`
- Dispatcher: `src/Monitoring/Domain/Service/MonitorDispatcher.php`
- Scheduler: `src/Monitoring/Infrastructure/Scheduler/MonitoringScheduler.php`

**Notifications**:
- Service: `src/Monitoring/Application/Service/AlertNotificationService.php`
- AlertRule: `src/Monitoring/Domain/Model/Alert/AlertRule.php`
- EscalationPolicy: `src/Monitoring/Domain/Model/Alert/EscalationPolicy.php`
- Controllers: `src/Monitoring/Infrastructure/Controller/AlertRuleController.php`

**Telemetry**:
- DTO: `src/Telemetry/Model/CheckResultDto.php`
- Ingestor: `src/Telemetry/Application/TelemetryIngestor.php`
- Buffer: `src/Monitoring/Infrastructure/Redis/RedisTelemetryBuffer.php`

### 10.2 Console Commands

```bash
# Start workers
php bin/console messenger:consume async

# Start scheduler
php bin/console messenger:consume scheduler_monitoring

# Run scheduler manually (debugging)
php bin/console messenger:consume scheduler_monitoring --limit=1

# Ingest telemetry
php bin/console telemetry:ingest

# Create partitions
php bin/console telemetry:partition:create

# Cleanup old partitions
php bin/console telemetry:partition:cleanup --retention=3
```

### 10.3 Docker Commands

```bash
# Start all services
docker compose up -d

# View logs
docker compose logs -f worker

# Scale workers
docker compose up -d --scale worker=5

# Execute command in container
docker compose exec php php bin/console cache:clear

# Connect to Redis CLI
docker compose exec redis redis-cli

# Connect to RabbitMQ management
open http://localhost:15672
```

---

## 11. HANDOFF CHECKLIST

### 11.1 Code Review Priorities
- [ ] Review Monitor entity invariants
- [ ] Review CheckMonitorHandler error handling
- [ ] Review AlertNotificationService rate limiting logic
- [ ] Review EscalationPolicy exact matching behavior
- [ ] Review TelemetryIngestor batch size configuration
- [ ] Review partition maintenance strategy

### 11.2 Testing Priorities
- [ ] Unit tests for Monitor state transitions
- [ ] Integration tests for all command handlers
- [ ] End-to-end tests for escalation policies
- [ ] Load testing for 10K concurrent checks
- [ ] Failure scenario testing (RabbitMQ down, Redis down, etc.)

### 11.3 Deployment Checklist
- [ ] Configure production environment variables
- [ ] Set up database partitions for next 6 months
- [ ] Configure log aggregation (ELK/Loki)
- [ ] Set up monitoring dashboards (Grafana)
- [ ] Configure alerting (PagerDuty/Slack)
- [ ] Document runbook for incident response
- [ ] Set up backup strategy (MySQL dumps, Redis snapshots)
- [ ] Configure SSL/TLS for all services

### 11.4 Performance Tuning
- [ ] Tune MySQL InnoDB buffer pool size
- [ ] Tune RabbitMQ queue limits
- [ ] Tune Redis maxmemory policy
- [ ] Configure OPcache for PHP
- [ ] Enable HTTP/2 for API
- [ ] Configure CDN for static assets (if any)

---

## CONCLUSION

This architecture demonstrates a sophisticated understanding of:
- **Domain-Driven Design**: Clear bounded contexts and aggregates
- **High-Performance Patterns**: Write buffering, bulk operations, partitioning
- **Distributed Systems**: Message queues, idempotency, rate limiting
- **Scalability**: Horizontal scaling, auto-scaling, cloud-native design

**Key Design Decisions**:
1. **DDD Separation**: Monitoring vs Telemetry contexts prevent performance trade-offs
2. **Thundering Herd Solution**: Batching prevents overload at scale
3. **Write Buffering**: Redis enables high-throughput ingestion
4. **Escalation Policies**: Exact matching prevents spam while ensuring alerts
5. **Rate Limiting**: Cooldown intervals prevent notification fatigue

**Next Steps for Implementation**:
1. Implement Telemetry context (DTOs, Ingestor, Repository)
2. Create database partitions
3. Set up partition maintenance cron jobs
4. Implement query layer for analytics
5. Build admin dashboard for telemetry visualization

---

**Document Version**: 1.0
**Last Updated**: 2026-01-26
**Author**: Claude (Sonnet 4.5)
**Status**: Ready for handoff
