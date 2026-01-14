# Project Blueprint: UptimeSentinel

**UptimeSentinel** is a high-performance, distributed uptime monitoring system designed to teach advanced System Design, Infrastructure, and Scalability concepts. It mimics a production-grade SaaS (like Pingdom) capable of handling massive write throughput and concurrency.

---

## 1. Executive Summary

* **Core Concept:** Distributed monitoring of thousands of URLs with real-time alerting.
* **Primary Goal:** Master "Hard" Engineering skills: System Design, Advanced MySQL (Partitioning), High-Concurrency Queues, and Caching.
* **Architecture Style:** **Distributed Modular Monolith**. Single repository, but deployed as distinct "Web" and "Worker" services in production.

---

## 2. Technology Stack & Architecture

We have chosen technologies that specifically support **Horizontal Scaling** and **High Throughput**.

| Component | Technology | "Senior" Reasoning |
| :--- | :--- | :--- |
| **Language** | **PHP 8.4** | Utilization of Readonly classes and typed constants for performance. |
| **Framework** | **Symfony 8** | Best-in-class `Messenger` component for complex queuing and `Console` for workers. |
| **Database** | **MySQL 8.0** | Chosen specifically for **Table Partitioning** to handle 10M+ rows/month. |
| **Queue** | **RabbitMQ** | Superior to Redis for complex routing (Exchanges, Dead Letter Queues) and reliability. |
| **Cache/Buffer** | **Redis** | Used for API Caching (Reads) and Write Buffering (Workers → Redis → MySQL). |
| **Web Server** | **Nginx** | Reverse Proxy to serve PHP-FPM and handle static assets (Phase 2). |
| **Orchestration** | **Docker** | Phase 1: Infrastructure only; Phase 2: Full environment isolation. |
| **CI/CD** | **GitHub Actions** | Automated testing, CS-Fixing, and Docker Build validation. |

---

## 3. Business Cases (Vertical Slices)

The project is divided into three "Gyms" to train specific scalability muscles.

### Case A: The Scheduler (The "Thundering Herd")
* **Problem:** Checking 10,000 URLs every 60 seconds using a simple loop causes timeouts and CPU spikes.
* **Solution:** A **Dispatcher** runs every minute (Cron), identifies "due" monitors, and pushes lightweight `Job IDs` to RabbitMQ. The Dispatcher does *not* perform the check.

### Case B: The Telemetry Engine (The "Big Data" Writer)
* **Problem:** 10,000 checks/minute = 10,000 DB Transactions/minute. This kills the database.
* **Solution:** **Write Buffering**. Workers push results to a Redis List. A specialized "Ingestor" process pops 1,000 items and performs a single **Bulk Insert** SQL statement into MySQL.

### Case C: The Dashboard (The "Complex Read")
* **Problem:** Calculating "Average Latency per Hour" over 30 days requires scanning 40M+ rows.
* **Solution:** **MySQL Range Partitioning** on `created_at` and **Covering Indexes** to make analytical queries instant.

---

## 4. Project File Structure

We use a **Hybrid Modular Monolith**. We apply strict DDD where logic is complex, and "Raw Data" patterns where speed is critical.

```text
uptime-sentinel/
├── .github/
│   └── workflows/          # CI/CD Pipelines (Tests, CS-Fixer)
├── config/                 # Symfony Config
├── public/
├── src/
│   ├── Kernel.php
│   │
│   ├── Monitor/            # [Context: DDD - Configuration]
│   │   ├── Application/    # Command Handlers (CreateMonitor, DisableMonitor)
│   │   ├── Domain/         # Entities (Monitor, AlertRule, MonitorId)
│   │   └── Infrastructure/ # Doctrine Repositories
│   │
│   ├── Telemetry/          # [Context: Raw Data - High Performance]
│   │   ├── Model/          # DTOs (PingResultDto) - NO Entities used here!
│   │   ├── Ingestion/      # BulkIngestorService (Redis -> MySQL Raw SQL)
│   │   └── Reporting/      # Read-heavy SQL queries for charts
│   │
│   └── Shared/             # ValueObjects (UuidV7), Shared Kernel
│
├── tests/
├── compose.yaml            # Infrastructure services (Phase 1 & 2)
└── DOCKER.md              # Docker setup documentation
```

---

## 5. Phase 1: Local Development (Simplified Setup)

**Goal:** Focus on system design, caching strategies, and architecture without Docker complexity.

### Architecture

```
┌─────────────────────────────────────────────────┐
│         Your Local Machine (macOS/Linux)        │
│                                                 │
│  ┌──────────────┐      ┌──────────────┐        │
│  │ PHP 8.4      │      │ Symfony CLI  │        │
│  │ (Herd/CLI)   │      │ or Built-in  │        │
│  └──────────────┘      └──────────────┘        │
│         │                      │                │
│         └──────────┬───────────┘                │
│                    │                            │
│         ┌──────────▼──────────┐                 │
│         │  Symfony 8 App      │                 │
│         │  - Web Server       │                 │
│         │  - Worker (local)   │                 │
│         └──────────┬──────────┘                 │
└────────────────────┼────────────────────────────┘
                     │
         ┌───────────▼─────────────────────┐
         │      Docker Compose             │
         │  ┌──────────────────────────┐  │
         │  │ MySQL 8.0    (port 3306) │  │
         │  ├──────────────────────────┤  │
         │  │ RabbitMQ 3  (5672, 15672)│  │
         │  ├──────────────────────────┤  │
         │  │ Redis 7      (port 6379) │  │
         │  └──────────────────────────┘  │
         └─────────────────────────────────┘
```

### Services

| Service | Technology | Location | Purpose |
|---------|------------|----------|---------|
| **Web** | PHP 8.4 + Symfony 8 | Local (Herd/CLI) | HTTP requests |
| **Workers** | `messenger:consume` | Local (CLI) | Background jobs |
| **Database** | MySQL 8.0 | Docker (port 3306) | Data persistence |
| **Queue** | RabbitMQ 3 | Docker (5672, 15672) | Message broker |
| **Cache** | Redis 7 | Docker (port 6379) | Caching & buffering |

### Development Workflow

```bash
# 1. Start infrastructure services
docker compose up -d

# 2. Run Symfony locally
symfony server:start
# or use Herd (auto-starts on file changes)

# 3. Run workers locally (in separate terminal)
php bin/console messenger:consume async

# 4. Run migrations
php bin/console doctrine:migrations:migrate
```

### Benefits

- **Fast iteration**: No container rebuilds needed
- **Easy debugging**: Full Xdebug support, direct code access
- **Production-like infrastructure**: Real MySQL, RabbitMQ, Redis
- **Simple scaling**: Run multiple worker processes locally
- **Focus on architecture**: Less DevOps, more system design

### When to Use Phase 1

- Learning DDD and architectural patterns
- Implementing caching strategies
- Building domain logic
- Developing business features
- Testing system design concepts

---

## 6. Phase 2: Production Deployment (Advanced Setup)

**Goal:** Production-grade infrastructure with horizontal scaling, isolation, and resilience.

### Architecture

```
                    ┌─────────────────┐
                    │   Load Balancer │
                    │    (Nginx)      │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │                              │
      ┌───────▼────────┐            ┌───────▼────────┐
      │  Web Service   │            │  Web Service   │
      │  (PHP-FPM)     │  ...       │  (PHP-FPM)     │
      └───────┬────────┘            └───────┬────────┘
              │                              │
              └──────────┬───────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
┌───────▼────────┐ ┌────▼────┐ ┌────────▼─────┐
│  MySQL 8.0     │ │RabbitMQ │ │  Redis 7     │
│  (Partitioned) │ │         │ │              │
└────────────────┘ └─────────┘ └──────────────┘
        │                │
        │         ┌──────┴──────────┐
        │         │                 │
┌───────▼────────┐ ┌────────▼────────┐
│  Worker 1      │ │  Worker N        │
│  (messenger:   │ │  (messenger:     │
│   consume)     │ │   consume)       │
└────────────────┘ └──────────────────┘
```

### Services

All services run in Docker containers with proper isolation and scaling capabilities.

| Service | Container Type | Scaling | Purpose |
|---------|---------------|---------|---------|
| **nginx** | Stateless | Horizontal | Load balancer & static files |
| **app** (web) | Stateless | Horizontal | PHP-FPM for HTTP requests |
| **worker** | Stateless | Horizontal | Background job consumers |
| **database** | Stateful | Replication | MySQL with partitioning |
| **rabbitmq** | Stateful | Cluster | Message queue (DLQ, exchanges) |
| **redis** | Stateful | Replication | Cache & write buffer |

### Production Features

1. **Multi-stage Docker Build**
   - Builder stage: Dependencies + compilation
   - Production stage: Minimal runtime image
   - Optimized for layer caching and image size

2. **Horizontal Scaling**
   ```bash
   # Scale web services
   docker compose up -d --scale app=3

   # Scale workers independently
   docker compose --profile worker up -d --scale worker=5
   ```

3. **Service Profiles**
   - Default: Web services only
   - `--profile worker`: Include worker services
   - Separate lifecycle for web vs worker

4. **Health Checks**
   - Database: `mysqladmin ping`
   - RabbitMQ: `rabbitmq-diagnostics ping`
   - Redis: `redis-cli ping`
   - Dependencies wait for health before starting

5. **Advanced MySQL**
   - Range partitioning on `created_at`
   - Covering indexes for analytical queries
   - Bulk inserts from write buffer

6. **Write Buffering**
   - Workers → Redis List (1,000 items)
   - Ingestor → Bulk INSERT (1 transaction)
   - Reduces DB load by 1000x

### Production Workflow

```bash
# 1. Build production images
docker compose build

# 2. Start all services
docker compose up -d

# 3. Scale workers based on load
docker compose --profile worker up -d --scale worker=5

# 4. Monitor via management UIs
# RabbitMQ: http://localhost:15672
```

### Migration Path: Phase 1 → Phase 2

1. Add `Dockerfile` (multi-stage build)
2. Add `docker/php/` configs (php.ini, opcache.ini, entrypoint.sh)
3. Add `docker/nginx/nginx.conf`
4. Add service definitions to `compose.yaml` for nginx, app, worker
5. Update deployment scripts
6. Configure CI/CD pipeline for container registry

### When to Use Phase 2

- Deploying to production
- Need horizontal scaling
- Multiple developers need isolated environments
- CI/CD pipeline testing
- Performance testing at scale

---

## 7. Installation & Packages

**Step 1: Initialize Project**

```bash
composer create-project symfony/skeleton:"8.0.*" uptime-sentinel
cd uptime-sentinel
```

**Step 2: Install Core Architecture Packages**

```bash
# Core logic, Database & UUIDs
composer require symfony/orm-pack symfony/uid symfony/console symfony/dotenv

# The System Design Heavy-Lifters
composer require symfony/messenger symfony/amqp-messenger # RabbitMQ Transport
composer require predis/predis                            # Redis Client

# Validation & Serialization
composer require symfony/validator symfony/serializer
```

**Step 3: Developer Tools**

```bash
composer require --dev symfony/maker-bundle symfony/test-pack symfony/profiler-pack
```

---

## 8. CI/CD Pipeline (GitHub Actions)

Every push triggers the `.github/workflows/ci.yaml` pipeline:

* **Quality Gate**: Runs `php-cs-fixer` and `phpstan` (Level 7+).
* **Integration Tests**: Spins up ephemeral MySQL/Redis containers and runs `phpunit`.
* **Build Verification** (Phase 2): Builds the Docker image to ensure the Dockerfile is production-ready.

---

## 9. Development Philosophy

### Phase 1: Architecture First
- Focus on **Domain-Driven Design**
- Implement **caching strategies** (Redis)
- Build **message-driven architecture** (RabbitMQ)
- Learn **system design patterns** (dispatcher, worker, bulk ingest)
- Fast feedback loop with local development

### Phase 2: Scalability & Operations
- **Horizontal scaling** with containers
- **Production isolation** and resilience
- **Performance optimization** (OPcache, partitioning)
- **Operational excellence** (health checks, monitoring)
- Deployment automation

### Key Insight

You don't need Docker complexity to learn system design. **Phase 1** lets you master the architecture patterns. **Phase 2** teaches you production deployment and scaling. Both are valuable, but they address different skill sets.

---

## 10. Recommended Learning Path

1. **Start with Phase 1** (current setup)
   - Build the domain model
   - Implement the dispatcher/workers pattern
   - Add caching layer
   - Experiment with write buffering

2. **Master the Fundamentals**
   - DDD bounded contexts
   - Message queues (RabbitMQ)
   - Caching strategies (Redis)
   - Database design (indexes, normalization)

3. **Graduate to Phase 2**
   - Container orchestration
   - Service scaling
   - Production deployment
   - Monitoring and observability

---

## Summary

- **Phase 1**: Local PHP + Docker infrastructure → **Learn Architecture & System Design**
- **Phase 2**: Full containerization → **Learn DevOps & Production Scaling**

Both phases use the same codebase and business logic. The difference is only in deployment strategy.
