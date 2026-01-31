# 📡 UptimeSentinel

**UptimeSentinel** is a distributed system for monitoring service availability at scale. It utilizes a three-tier aggregation strategy and high-performance data pipelines to handle telemetry data with long-term analytical storage.

> **Project Goal:** This is a "System Design Gym" implementation focused on solving real-world scalability problems like the Thundering Herd effect, massive table growth, and high-concurrency write buffering.

## 🏗 Architecture Highlights

| Component | Technology          | Role |
| :--- |:--------------------| :--- |
| **Core Framework** | Symfony 7 (PHP 8.4) | Modular Monolith with DDD patterns |
| **Orchestration** | Docker Compose      | Service isolation (Web, Workers, Ingestors) |
| **Queue Broker** | RabbitMQ            | Asynchronous job processing & task decoupling |
| **Storage Strategy**| MySQL 8.0           | **Three-Tier Aggregation** & **Dynamic Partitioning** |
| **Write Buffer** | Redis               | Multi-staged buffering for ingestion throughput |

## 🚀 Key Engineering Challenges Solved

### 1. The "Thundering Herd" Scheduler
Instead of a naive loop, a **Dispatcher** runs periodically to push lightweight Job IDs to RabbitMQ. Distributed **Workers** consume these batches concurrently using non-blocking I/O (Curl Multi), preventing system overload during peak check times.

### 2. Multi-Tier Telemetry Pipeline
We implement a high-efficiency ingestion pipeline:
- **Tier 1 (Raw)**: Workers buffer results in Redis; a scheduled **Ingestor** performs bulk inserts into partitioned MySQL tables.
- **Tier 2 (Hourly)**: Scheduled rollups aggregate raw data into hourly performance buckets.
- **Tier 3 (Daily)**: Long-term summaries for indefinite historical reporting.

### 3. Automated Data Lifecycle
To keep the database lean and fast, the system implements **Automatic Partition Management**:
- **Future Partitioning**: Automatically creates future partitions for the next 7 days.
- **Data Pruning**: Automatically drops raw data partitions older than 30 days while preserving aggregated stats.

## 🛠 Installation (Hybrid Mode)

**Prerequisites:**
* PHP 8.4+
* Composer
* Docker (for Infrastructure only)

```bash
# 1. Clone the repo
git clone [https://github.com/anarzone/uptime-sentinel.git](https://github.com/anarzone/uptime-sentinel.git)

# 2. Start Infrastructure (MySQL, RabbitMQ, Redis)
docker compose up -d

# 3. Start the Application
symfony server:start
# Test hook
# Test hook again
# Test GLM Integration
