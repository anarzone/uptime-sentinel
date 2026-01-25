# UptimeSentinel - Business Logic Explained

This document explains the business logic from the ground up.

---

## Part 1: What Are We Building?

### The Product: Uptime Monitoring Service

Think of services like **Pingdom**, **UptimeRobot**, or **StatusCake**. They:

1. **Monitor websites/APIs** - Check if they're online
2. **Alert when down** - Send email/Slack when something breaks
3. **Show history** - Dashboard with uptime percentages and response times

### Our Core Feature

> **"Check if a URL is responding correctly, every X seconds"**

Example: A user wants to monitor `https://my-shop.com` every 60 seconds and get alerted if it returns anything other than HTTP 200.

---

## Part 2: The Simple Version (What Beginners Build)

A naive implementation would be:

```php
// ❌ BAD: Simple loop approach
while (true) {
    $monitors = $database->query("SELECT * FROM monitors");
    
    foreach ($monitors as $monitor) {
        $response = httpClient->get($monitor->url);
        
        $database->insert('check_results', [
            'monitor_id' => $monitor->id,
            'status_code' => $response->statusCode,
            'latency' => $response->time,
        ]);
    }
    
    sleep(60);
}
```

### Why This Breaks at Scale

| Monitors | Time per Check | Total Time | Problem |
|----------|---------------|------------|---------|
| 10 | 500ms | 5 seconds | ✅ Fine |
| 100 | 500ms | 50 seconds | ⚠️ Tight |
| 1,000 | 500ms | 500 seconds | ❌ 8+ minutes! |
| 10,000 | 500ms | 5,000 seconds | ❌ 83 minutes! |

If each check takes 500ms (network timeout), checking 10,000 URLs **sequentially** takes over an hour. But we need to check them **every minute**!

---

## Part 3: The "Thundering Herd" Problem

### What Is It?

Imagine 10,000 alarms all going off at the same time. Your single process can't handle them all.

```
Minute 0:00 → 10,000 monitors due → Single process overwhelmed → Timeouts → Failures
Minute 1:00 → 10,000 monitors due → Same problem
```

### The Real-World Analogy

**Bad approach:** One cashier serving 10,000 customers in a queue.

**Good approach:** 100 cashiers, each serving 100 customers in parallel.

---

## Part 4: The Solution Architecture

We split the work into **three separate concerns**:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         THE SOLUTION                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│   1. SCHEDULER          2. DISPATCHER         3. WORKERS                │
│   (When to check)       (What to check)       (Do the check)            │
│                                                                          │
│   ┌─────────────┐      ┌─────────────┐      ┌─────────────┐             │
│   │ Every 1 min │ ──▶  │ Find due    │ ──▶  │ Worker 1    │             │
│   │ trigger     │      │ monitors    │      │ Worker 2    │             │
│   │ dispatcher  │      │ Push to     │      │ Worker 3    │             │
│   └─────────────┘      │ queue       │      │ ...         │             │
│                        └─────────────┘      │ Worker N    │             │
│                                             └─────────────┘             │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Role of Each Component

| Component | Responsibility | Runs |
|-----------|---------------|------|
| **Scheduler** | Trigger the dispatcher every minute | Continuously (Symfony Scheduler) |
| **Dispatcher** | Find monitors that need checking, push their IDs to queue | When triggered (fast, <1 sec) |
| **Workers** | Take a monitor ID from queue, perform HTTP check, save result | Continuously, in parallel |

---

## Part 5: The Flow Step-by-Step

### Step 1: User Creates a Monitor

```
User → API → "I want to monitor https://google.com every 60 seconds"
                                        │
                                        ▼
                              ┌─────────────────────┐
                              │     Database        │
                              │─────────────────────│
                              │ id: abc-123         │
                              │ url: google.com     │
                              │ interval: 60 sec    │
                              │ status: ACTIVE      │
                              │ next_check_at: NOW  │ ← Important!
                              └─────────────────────┘
```

The key field is `next_check_at`. When created, it's set to NOW (check immediately).

---

### Step 2: Scheduler Triggers (Every Minute)

```
┌──────────────────────┐
│   Symfony Scheduler  │
│                      │
│  "It's been 1 minute │
│   since last trigger"│
│                      │
│         │            │
│         ▼            │
│  Dispatch Message:   │
│  DispatchMonitors    │
└──────────────────────┘
```

The scheduler doesn't do any work. It just says: "Hey, it's time to check which monitors are due."

---

### Step 3: Dispatcher Chunks Due Monitors

The Dispatcher finds all monitors due for a check, but instead of sending one message per monitor, it groups them to reduce queue overhead.

```php
// The Dispatcher logic:
$dueMonitors = $repository->findDueMonitors();
$ids = array_map(fn($m) => $m->id, $dueMonitors);

// Split 10,000 IDs into batches of 50
$batches = array_chunk($ids, 50);

foreach ($batches as $batch) {
    $this->messageBus->dispatch(new CheckMonitorBatchCommand($batch));
}
```

**Key insight:** Sending 200 batch messages is much easier for RabbitMQ to handle than 10,000 individual messages.

---

### Step 4: Workers Perform Async Checks

Workers grab a batch of 50 IDs and check them **simultaneously** using `curl_multi` via Symfony HttpClient.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           BATCH WORKERS                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Worker 1 grabs: CheckMonitorBatchCommand (50 IDs)                       │
│    1. Fetch all 50 monitors from DB (1 query!)                           │
│    2. Fire 50 HTTP requests SIMULTANEOUSLY                               │
│    3. As results stream back:                                            │
│       a. Push result to Telemetry Buffer (Redis)                         │
│       b. Update monitor "next_check_at"                                  │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

**Key insight:** By using `curl_multi`, a single worker can check 50 websites at the same time. If each check takes 1s, the worker finishes the whole batch in ~1.2s (plus overhead).

---

## Part 6: Why This Scales

### The Math

With the queue-based approach:

| Workers | Checks per Second | Checks per Minute |
|---------|------------------|-------------------|
| 1 | 2 | 120 |
| 5 | 10 | 600 |
| 10 | 20 | 1,200 |
| 50 | 100 | 6,000 |
| 100 | 200 | 12,000 |

**To handle 10,000 monitors checked every minute, you need ~84 workers.** Just scale horizontally!

### The Beauty

1. **Dispatcher stays fast** - Only pushes IDs, never waits for HTTP
2. **Workers are stateless** - Add more workers to handle more load
3. **Queue handles backpressure** - If workers are slow, messages wait in queue
4. **No single point of failure** - One worker crash doesn't affect others

---

## Part 7: Code Implementation

Now let's see the code for each component.

### 7.1 The Monitor Entity

```php
// src/Monitoring/Domain/Model/Monitor/Monitor.php

final class Monitor
{
    public readonly MonitorId $id;
    public readonly string $name;
    public readonly Url $url;
    public readonly HttpMethod $method;
    public readonly int $intervalSeconds;    // How often to check
    public readonly int $timeoutSeconds;     // Max wait time
    public readonly int $expectedStatusCode; // What counts as "success"
    
    public MonitorStatus $status;            // ACTIVE or PAUSED
    public ?\DateTimeImmutable $lastCheckedAt;
    public \DateTimeImmutable $nextCheckAt;  // When to check next
    
    /**
     * Called after each check to schedule the next one.
     */
    public function markChecked(\DateTimeImmutable $checkedAt): void
    {
        $this->lastCheckedAt = $checkedAt;
        $this->nextCheckAt = $checkedAt->modify("+{$this->intervalSeconds} seconds");
    }
    
    /**
     * Is this monitor due for checking?
     */
    public function isDue(\DateTimeImmutable $now = new \DateTimeImmutable()): bool
    {
        return $this->status === MonitorStatus::ACTIVE 
            && $this->nextCheckAt <= $now;
    }
}
```

---

### 7.2 The Scheduler (Triggers Dispatcher)

```php
// src/Monitoring/Infrastructure/Scheduler/MonitoringScheduleProvider.php

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('monitoring')]
final class MonitoringScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                // Every 1 minute, dispatch a "time to check" message
                RecurringMessage::every('1 minute', new DispatchMonitorsMessage())
            );
    }
}
```

```php
// src/Monitoring/Infrastructure/Scheduler/DispatchMonitorsMessage.php

// This is just a trigger - no data needed
final readonly class DispatchMonitorsMessage
{
}
```

---

### 7.3 The Dispatcher (Finds Due Monitors, Pushes to Queue)

```php
// src/Monitoring/Infrastructure/Scheduler/DispatchMonitorsHandler.php

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DispatchMonitorsHandler
{
    public function __construct(
        private MonitorDispatcher $dispatcher,
    ) {}

    public function __invoke(DispatchMonitorsMessage $message): void
    {
        $this->dispatcher->dispatchDueMonitors();
    }
}
```

```php
// src/Monitoring/Domain/Service/MonitorDispatcher.php

use App\Monitoring\Application\Command\CheckMonitor\CheckMonitorCommand;

final readonly class MonitorDispatcher
{
    public function __construct(
        private MonitorRepositoryInterface $repository,
        private MessageBusInterface $messageBus,
    ) {}

    public function dispatchDueMonitors(): int
    {
        $dueMonitors = $this->repository->findDueMonitors();
        
        foreach ($dueMonitors as $monitor) {
            // Dispatch Command (not Message) to Application layer
            $this->messageBus->dispatch(
                new CheckMonitorCommand($monitor->id->toString())
            );
        }
        
        return count($dueMonitors);
    }
}
```

---

### 7.4 The Repository Query

```php
// src/Monitoring/Infrastructure/Persistence/MonitorRepository.php

final class MonitorRepository implements MonitorRepositoryInterface
{
    public function findDueMonitors(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.nextCheckAt <= :now')    // Due for checking
            ->andWhere('m.status = :status')    // Only active monitors
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', MonitorStatus::ACTIVE)
            ->getQuery()
            ->getResult();
    }
}
```

---

### 7.5 The Command (Application Layer)

```php
// src/Monitoring/Application/Command/CheckMonitor/CheckMonitorCommand.php

namespace App\Monitoring\Application\Command\CheckMonitor;

// Command (CQRS) - lives in Application layer, not Infrastructure
final readonly class CheckMonitorCommand
{
    public function __construct(
        public string $monitorId,
    ) {}
}
```

### 7.5b Domain Interfaces

```php
// src/Monitoring/Domain/Service/UrlCheckerInterface.php

interface UrlCheckerInterface
{
    public function check(Monitor $monitor): CheckResultDto;
}
```

```php
// src/Monitoring/Domain/Service/TelemetryBufferInterface.php

interface TelemetryBufferInterface
{
    public function push(CheckResultDto $result): void;
}
```

---

### 7.6 The Batch Handler (Application Layer)

```php
// src/Monitoring/Application/Command/CheckMonitorBatch/CheckMonitorBatchHandler.php

#[AsMessageHandler]
final readonly class CheckMonitorBatchHandler
{
    public function __invoke(CheckMonitorBatchCommand $command): void
    {
        // 1. Fetch 50 monitors in ONE query
        $monitors = $this->monitorRepository->findByIds($command->monitorIds);

        // 2. Fire 50 concurrent requests (Async I/O)
        $results = $this->urlChecker->checkBatch($monitors);

        // 3. Process results as they stream in
        foreach ($results as $result) {
            $this->telemetryBuffer->push($result);
            // ... update next_check_at
        }
    }
}
```

### 7.6b Infrastructure Implementations

```php
// src/Monitoring/Infrastructure/Http/SymfonyHttpUrlChecker.php

final readonly class SymfonyHttpUrlChecker implements UrlCheckerInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function check(Monitor $monitor): CheckResultDto
    {
        // HTTP check logic here - returns CheckResultDto
    }
}
```

```php
// src/Monitoring/Infrastructure/Redis/RedisTelemetryBuffer.php

final readonly class RedisTelemetryBuffer implements TelemetryBufferInterface
{
    public function __construct(
        private RedisClient $redis,
    ) {}

    public function push(CheckResultDto $result): void
    {
        $this->redis->lpush('telemetry_buffer', [json_encode($result->toArray())]);
    }
}
```

---

### 7.7 Running It All

```bash
# Terminal 1: Run the scheduler (triggers dispatcher every minute)
php bin/console messenger:consume scheduler_monitoring -vv

# Terminal 2: Run workers (process check jobs)
php bin/console messenger:consume async -vv

# Want more workers? Open more terminals or use Supervisor:
php bin/console messenger:consume async -vv  # Worker 2
php bin/console messenger:consume async -vv  # Worker 3
```

---

---

## Part 9: Telemetry Ingestion Flow (The Write Accelerator)

To handle 10,000 results per minute without crashing the database, we use **Write Buffering**.

1.  **Buffer**: Workers push results to a Redis List (`LPUSH telemetery_queue`). This is lightning fast (<1ms).
2.  **Ingestor**: A separate background process runs every few seconds.
3.  **Bulk Insert**: The Ingestor grabs 1,000 items from Redis and performs a **single SQL statement** to MySQL.

```sql
-- One query instead of 1,000!
INSERT INTO check_results (id, monitor_id, status_code, latency, created_at) 
VALUES 
(..., 200, 150, ...),
(..., 503, 0, ...),
... (998 more) ...
```

---

## Part 10: Dashboard Caching (The Read Strategy)

History charts can scan millions of rows. To keep the UI snappy, we use **Cache-Aside**.

1.  **Check Cache**: API looks for stats in Redis (`GET monitor_stats_{id}`).
2.  **Hit**: Return JSON immediately (20ms).
3.  **Miss**: Run the heavy SQL query, save result to Redis with a 5-minute TTL, and return to user.

---

## Part 11: Summary

### The Three "Gyms"

| Case | Problem | Solution |
|------|---------|----------|
| **Scheduler** | 10K checks/min = Thundering Herd | Batching + Async HTTP (`curl_multi`) |
| **Telemetry** | 10K writes/min = DB death | Redis Buffer + Bulk SQL Ingestion |
| **Dashboard** | 100M rows = Slow reads | Range Partitioning + Redis Caching |

### Key Takeaways

1. **Batching is life**: Group 50 requests in a batch, group 1,000 results in an ingest.
2. **Time != Trigger**: Use a reliable Scheduler (Cron) to kick off the work.
3. **Stateless Workers**: Increase workers (Docker scale) to handle infinite growth.
4. **Cache the result, not the query**: Store pre-calculated stats for the dashboard.
