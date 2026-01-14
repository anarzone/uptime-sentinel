# 📡 UptimeSentinel

**UptimeSentinel** is a distributed system for monitoring service availability at scale. It is engineered to handle **10,000+ concurrent checks per minute** using a hybrid architecture that combines Domain-Driven Design (DDD) for configuration with raw, high-performance data pipelines for telemetry.

> **Project Goal:** This is a "System Design Gym" implementation focused on solving real-world scalability problems like the Thundering Herd effect, massive table growth, and high-concurrency write buffering.

## 🏗 Architecture Highlights

| Component | Technology | Role |
| :--- | :--- | :--- |
| **Core Framework** | Symfony 7 (PHP 8.3) | Modular Monolith architecture |
| **Orchestration** | Docker Compose | Service isolation (Web vs. Worker) |
| **Queue Broker** | RabbitMQ | Handling the "Thundering Herd" of scheduled checks |
| **Database** | MySQL 8.0 | Using **Table Partitioning** for 10M+ log rows/month |
| **Write Buffer** | Redis | Buffering high-velocity writes before disk persistence |

## 🚀 Key Engineering Challenges Solved

### 1. The "Thundering Herd" Scheduler
Instead of a naive `foreach` loop that times out, a **Dispatcher** runs every minute to push lightweight Job IDs to RabbitMQ. Distributed **Workers** consume these jobs at their own pace, preventing system overload during peak check times.

### 2. High-Throughput Ingestion
Writing 10,000 logs/minute kills standard database connections. We implement a **Bulk Ingestion Pipeline** where workers push results to a Redis List, and a dedicated **Ingestor Service** performs single-transaction bulk inserts into MySQL.

### 3. Big Data Analytics
To query "Average Latency over 30 days" across millions of rows, we utilize **MySQL Range Partitioning** and **Covering Indexes**, turning slow analytical queries into sub-second operations.

## 🛠 Installation (Hybrid Mode)

**Prerequisites:**
* PHP 8.3+
* Composer
* Docker (for Infrastructure only)

```bash
# 1. Clone the repo
git clone [https://github.com/anarzone/uptime-sentinel.git](https://github.com/anarzone/uptime-sentinel.git)

# 2. Start Infrastructure (MySQL, RabbitMQ, Redis)
docker compose up -d

# 3. Start the Application
symfony server:start
