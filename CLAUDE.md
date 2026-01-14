# UptimeSentinel - Claude Code Guidelines

This document defines the coding standards, architectural principles, and review criteria for the UptimeSentinel project. When reviewing code or implementing features, follow these guidelines to ensure consistency and quality.

## Project Overview

**UptimeSentinel** is a distributed uptime monitoring system engineered to handle **10,000+ concurrent checks per minute** using a hybrid architecture combining Domain-Driven Design (DDD) with high-performance data pipelines.

### Architecture Style

- **Distributed Modular Monolith**: Single repository deployed as distinct "Web" and "Worker" services
- **Phase 1**: Local PHP + Docker infrastructure (current)
- **Phase 2**: Full containerization with horizontal scaling (future)

### Technology Stack

- **Language**: PHP 8.4 (strict types, readonly properties)
- **Framework**: Symfony 8 (Messenger component for queues)
- **Database**: MySQL 8.0 with table partitioning for 10M+ rows/month
- **Queue**: RabbitMQ (handling Thundering Herd problem)
- **Cache/Buffer**: Redis (write buffering, API caching)
- **CI/CD**: GitHub Actions with semantic release

## Code Quality Standards

### PHP Coding Standards

All code must pass **PHP CS Fixer** with `@Symfony` and `@Symfony:risky` rules:

```bash
composer check-style  # Must pass
composer format       # Auto-fix issues
```

**Key Requirements**:
- `declare(strict_types=1)` at the top of every PHP file
- Use `readonly` properties for immutable value objects
- Follow PSR-12 coding standard
- Use short array syntax `[]`
- No trailing whitespace
- Proper line breaks and indentation

### Static Analysis

All code must pass **PHPStan** at Level 5:

```bash
composer analyze  # Must pass with 0 errors
```

Focus on:
- Type safety for all parameters and return types
- No undefined variables or methods
- Proper null safety handling
- Generic types for collections

## Architectural Principles

### Domain-Driven Design (DDD)

The codebase is organized into **bounded contexts**:

#### 1. Monitor Context (`src/Monitor/`)
**Purpose**: Configuration and domain logic for monitoring
- **DDD Structure**: Strict DDD with aggregates, entities, value objects
- **Application Layer**: Command handlers (CreateMonitor, DisableMonitor)
- **Domain Layer**: Business rules and invariants
- **Infrastructure Layer**: Doctrine repositories

```php
// Example: Monitor entity with DDD patterns
namespace App\Monitor\Domain\Entity;

use App\Monitor\Domain\ValueObject\MonitorId;
use App\Monitor\Domain\ValueObject\Url;

final class Monitor
{
    private readonly MonitorId $id;
    private readonly Url $url;
    private bool $isActive;

    public function __construct(MonitorId $id, Url $url)
    {
        $this->id = $id;
        $this->url = $url;
        $this->isActive = true;
    }

    public function disable(): void
    {
        if (!$this->isActive) {
            throw new \DomainException('Monitor is already disabled');
        }
        $this->isActive = false;
    }
}
```

#### 2. Telemetry Context (`src/Telemetry/`)
**Purpose**: High-performance data ingestion and analytics
- **Raw Data Pattern**: NO entities (DTOs only)
- **Bulk Operations**: Raw SQL for performance
- **Write Buffering**: Redis → MySQL bulk inserts

```php
// Example: DTO for telemetry data
namespace App\Telemetry\Model;

final class PingResultDto
{
    public function __construct(
        public readonly string $monitorId,
        public readonly int $statusCode,
        public readonly int $latencyMs,
        public readonly \DateTimeImmutable $checkedAt
    ) {}
}
```

#### 3. Shared Context (`src/Shared/`)
**Purpose**: Cross-cutting concerns
- Value objects (UuidV7, Email, etc.)
- Shared kernel utilities
- Common interfaces

### Key Architectural Patterns

#### 1. Thundering Herd Solution (Scheduler)
**Problem**: 10K URL checks every 60s via loop = timeouts
**Solution**: Dispatcher → RabbitMQ → Workers pattern

```php
// Dispatcher (runs every minute via cron)
class MonitorDispatcher
{
    public function dispatchDueMonitors(): void
    {
        $monitors = $this->repository->findDueMonitors();
        foreach ($monitors as $monitor) {
            $this->bus->dispatch(new MonitorJob($monitor->getId()));
        }
    }
}

// Worker (consumes from RabbitMQ)
class MonitorJobHandler implements MessageHandlerInterface
{
    public function __invoke(MonitorJob $job): void
    {
        $result = $this->urlChecker->check($job->getMonitorId());
        $this->redis->lpush('telemetry_buffer', json_encode($result));
    }
}
```

#### 2. Write Buffering (High-Throughput Ingestion)
**Problem**: 10K DB transactions/min kills database
**Solution**: Workers → Redis → Bulk Ingestor → MySQL

```php
// Worker pushes to Redis buffer
$this->redis->lpush('telemetry_buffer', json_encode($result));

// Ingestor pops and bulk inserts
class TelemetryIngestor
{
    public function ingest(): void
    {
        $batch = [];
        for ($i = 0; $i < 1000; $i++) {
            $item = $this->redis->rpop('telemetry_buffer');
            if (!$item) break;
            $batch[] = json_decode($item, true);
        }

        $this->connection->executeStatement(
            'INSERT INTO telemetry (...) VALUES ' . implode(',', array_fill(0, count($batch), '(?,?,?)')),
            array_merge(...array_map(fn($r) => [$r['id'], $r['status'], $r['latency']], $batch))
        );
    }
}
```

#### 3. Table Partitioning (Big Data Analytics)
**Problem**: 30-day latency scan over 40M rows = slow
**Solution**: MySQL range partitioning + covering indexes

```sql
-- Partition by created_at
ALTER TABLE telemetry PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p202601 VALUES LESS THAN (UNIX_TIMESTAMP('2026-02-01')),
    PARTITION p202602 VALUES LESS THAN (UNIX_TIMESTAMP('2026-03-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Covering index for analytical queries
CREATE INDEX idx_telemetry_monitor_latency ON telemetry (monitor_id, latency, created_at);
```

## Security Best Practices

### Critical Security Rules

1. **Never commit secrets** - Use `.env.local` for local overrides
2. **Strong APP_SECRET** - Generate with `openssl rand -hex 32`
3. **Input validation** - Always validate user input with Symfony Validator
4. **Output encoding** - Use Twig auto-escaping for XSS prevention
5. **SQL injection** - Always use parameterized queries (Doctrine)
6. **CSRF protection** - Enable Symfony CSRF tokens on forms
7. **Authentication/Authorization** - Use Symfony Security component

### Security Review Checklist

- [ ] No hardcoded credentials or API keys
- [ ] All user input is validated
- [ ] Database queries use parameterized statements
- [ ] Sensitive data is encrypted at rest
- [ ] Proper error handling (no stack traces to users)
- [ ] Rate limiting on public endpoints
- [ ] CORS configured properly
- [ ] Dependencies are up-to-date

## Performance Standards

### Performance Targets

- **URL Check**: < 100ms p50, < 500ms p95
- **API Response**: < 200ms p50, < 1s p95
- **Throughput**: 10,000 checks/min per worker instance

### Optimization Guidelines

1. **Avoid N+1 Queries**: Use Doctrine batching or DQL
2. **Use Covering Indexes**: Index all columns needed for queries
3. **Cache Aggressively**: Redis for read-heavy operations
4. **Bulk Operations**: Batch inserts/updates (1000 items/transaction)
5. **Lazy Loading**: Doctrine lazy associations, fetch joins when needed
6. **OPcache**: Enable in production for Phase 2

### Performance Review Checklist

- [ ] No N+1 query problems
- [ ] Appropriate indexes exist
- [ ] Caching strategy defined
- [ ] Bulk operations for high-volume writes
- [ ] Query execution time analyzed
- [ ] Memory usage profiled

## Testing Standards

### Test Coverage Requirements

- **Unit Tests**: 80%+ coverage for domain logic
- **Integration Tests**: All repositories and message handlers
- **End-to-End Tests**: Critical workflows (schedule → check → ingest → query)

### Testing Best Practices

```php
// Example: Unit test with Zenstruck Foundry
namespace App\Tests\Unit\Monitor\Domain\Entity;

use App\Monitor\Domain\Entity\Monitor;
use App\Monitor\Domain\ValueObject\MonitorId;
use App\Monitor\Domain\ValueObject\Url;
use PHPUnit\Framework\TestCase;

class MonitorTest extends TestCase
{
    public function test_can_be_disabled(): void
    {
        $monitor = new Monitor(MonitorId::generate(), new Url('https://example.com'));

        $monitor->disable();

        $this->assertFalse($monitor->isActive());
    }

    public function test_cannot_be_disabled_twice(): void
    {
        $monitor = new Monitor(MonitorId::generate(), new Url('https://example.com'));
        $monitor->disable();

        $this->expectException(\DomainException::class);
        $monitor->disable();
    }
}
```

### Test Review Checklist

- [ ] Tests cover happy path
- [ ] Tests cover error cases
- [ ] Tests cover edge cases
- [ ] No test logic duplication (use data providers)
- [ ] Tests are independent (no shared state)
- [ ] Test names are descriptive

## Documentation Standards

### Required Documentation

1. **Inline Comments**: Only for "why", not "what"
2. **PHPDoc**: Required for all public methods
3. **Architecture Decision Records (ADRs)**: For major decisions
4. **README.md**: Update for feature changes
5. **PROJECT_BLUEPRINT.md**: Update for architecture changes

### PHPDoc Example

```php
/**
 * Dispatches monitors that are due for checking.
 *
 * This method finds all monitors whose next_check_at timestamp
 * is in the past and dispatches them to RabbitMQ for processing
 * by worker instances.
 *
 * @return int Number of monitors dispatched
 */
public function dispatchDueMonitors(): int
{
    // Implementation...
}
```

## Code Review Criteria

### Critical Issues (Must Fix)

- Security vulnerabilities (exposed secrets, injection attacks)
- Database queries without pagination (potential OOM)
- Missing validation on user input
- Hardcoded configuration values
- Blocking operations in async handlers
- Missing error handling for external services

### Major Improvements (Should Fix)

- Violation of DDD principles (domain logic in wrong layer)
- N+1 query problems
- Missing test coverage for critical paths
- Unclear naming or code organization
- Missing or incomplete PHPDoc
- Performance bottlenecks

### Minor Suggestions (Nice to Have)

- Code style consistency
- Extract complex logic into well-named methods
- Add more integration tests
- Improve error messages
- Add logging for debugging
- Optimization opportunities

## Review Output Structure

When providing code review feedback, structure it as:

1. **Summary**: 2-3 sentence overall assessment
2. **Strengths**: What's working well (specific examples)
3. **Critical Issues**: Problems that must be addressed
4. **Major Improvements**: Significant enhancements
5. **Minor Suggestions**: Nice-to-have improvements
6. **Learning Resources**: Relevant documentation or patterns
7. **Action Plan**: Prioritized changes with effort estimates

## Example Review Responses

### Positive Review Example

"Excellent implementation of the Monitor entity. The DDD patterns are well-applied with clear separation between domain and application layers. The value objects (MonitorId, Url) properly encapsulate domain concepts. The `disable()` method correctly guards against invalid state transitions with a domain exception. **No changes needed.**"

### Constructive Review Example

"**Summary**: The telemetry ingestion service shows good understanding of performance requirements, but has security and architecture concerns.

**Critical Issues**:
1. **SQL Injection Risk**: The bulk insert concatenates values instead of using parameters. Use parameterized statements.
2. **Missing Error Handling**: No try-catch around Redis operations. Connection failures will crash the worker.

**Major Improvements**:
1. **DDD Violation**: Raw SQL in Telemetry context is correct, but consider extracting to a dedicated `TelemetryRepository` class.
2. **No Batch Size Validation**: Hardcoded 1000 items could exceed MySQL `max_allowed_packet`. Make it configurable.

**Minor Suggestions**:
- Add metrics logging for batch size and processing time
- Consider using a transaction for atomicity

**Action Plan**:
1. Fix SQL injection (5 min)
2. Add error handling (10 min)
3. Extract to repository class (20 min)
4. Add batch size validation (10 min)
Total: ~45 minutes

Overall, good performance-focused approach. Address the critical security issue before merging."

## Resources

- **PROJECT_BLUEPRINT.md**: Full architectural documentation
- **Symfony Best Practices**: https://symfony.com/doc/current/best_practices.html
- **DDD by Example**: https://github.com/CleanArchitecture/DDD-by-examples
- **MySQL Partitioning**: https://dev.mysql.com/doc/refman/8.0/en/partitioning.html
- **Redis Patterns**: https://redis.io/docs/manual/patterns/

---

**Remember**: This is a "System Design Gym" project. The goal is to learn advanced scalability patterns. When reviewing code, balance strict standards with learning opportunities. Explain the "why" behind suggestions to foster understanding.
