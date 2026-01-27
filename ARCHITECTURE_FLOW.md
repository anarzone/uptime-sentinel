# Uptime Sentinel: Architectural Flow & System Overview

This document provides a comprehensive overview of the **Uptime Sentinel** architecture, detailing the flows for Monitoring, Notifications, and the proposed Telemetry system. It also explains the infrastructure role of RabbitMQ and Redis.

---

## 1. Monitoring Flow (The Pulse)

The monitoring system is built on a distributed, asynchronous model using Symfony Scheduler and Messenger.

### A. Scheduling & Dispatching
1.  **`MonitoringScheduler`**: A CRON-like trigger (every minute) that generates a `DispatchMonitorsMessage`.
2.  **`DispatchMonitorsHandler`**: Picks up the trigger and invokes the `MonitorDispatcher` domain service.
3.  **`MonitorDispatcher`**:
    - Queries the database for monitors where `next_check_at <= NOW`.
    - **Batching**: It chunks these monitors (e.g., 50 per batch) to maximize throughput.
    - **Queueing**: For each batch, it dispatches a `CheckMonitorBatchCommand` to **RabbitMQ**.

### B. Execution (The Worker)
1.  **`CheckMonitorBatchHandler`**: A pool of workers listens to the RabbitMQ `async` queue.
2.  **`UrlChecker`**: Performs concurrent HTTP/S checks for all monitors in the batch.
3.  **State Management**:
    - Each monitor's state (uptime, failures, last checked) is updated via `markChecked()`.
    - The updated entities are persisted to the database in a single transaction.
4.  **Handoff**: The handler passes the updated monitor to the `AlertNotificationService` for health evaluation.

---

## 2. Notification Flow (The Nerve System)

The notification system handles anomaly detection, escalation, and delivery through a decoupled architecture.

### A. Command Handlers (Management)
- **`AlertRule` Handlers**: Manage user-defined thresholds (e.g., "Alert me if site X is down 3 times").
- **`EscalationPolicy` Handlers**: Manage hierarchical escalation (e.g., "If site X is still down after 10 checks, notify the CTO").
- These handlers primarily deal with CRUD operations on the rules that the notification service evaluates.

### B. `AlertNotificationService` Evaluation
This service runs immediately after a check result is processed:
1.  **Transition Detection**: Compares the new status with the cached previous status (stored in Redis/DB).
2.  **Alert Rule Matching**: Iterates through active rules for the monitor. If `consecutiveFailures == rule.threshold`, it prepares a notification.
3.  **Escalation Logic**: Evaluates `EscalationPolicy` matches. It checks if the failures have reached higher tiers (Level 1, 2, etc.).
4.  **Deduplication**: Uses a **Redis-backed Rate Limiter** to ensure a "Notification Storm" doesn't occur (e.g., limited to 1 alert per hour per rule).

### C. Delivery (Async)
1.  **Dispatcher**: Decides whether to send an **Email** (Symfony Mailer) or a **Chat/SMS** (Symfony Notifier).
2.  **Infrastructure**:
    - Credentials (API keys, DSNs) are stored in the `NotificationChannel` entity.
    - Delivery is pushed back to **RabbitMQ** (via `async` transport) so that slow external APIs (SendGrid, Slack, Twilio) don't block the monitoring workers.

---

## 3. Telemetry Flow (The Memory - Planned)

We are preparing to implement a high-throughput telemetry system to provide historical insights.

### A. Ingestion (Current)
- Inside `CheckMonitorBatchHandler`, every check result is pushed to a **Redis List** (`telemetry_buffer`).
- This is a "fire-and-forget" push, ensuring the monitoring loop remains extremely fast.

### B. Processing (Proposed)
1.  **`TelemetryConsumer`**: A dedicated long-running process that use `RPOP` or `BLPOP` to consume results from Redis.
2.  **Persistence**: Data is written to a specialized storage engine (e.g., **TimescaleDB** or **InfluxDB**) to handle millions of data points.
3.  **Aggregator**: Periodically calculates metrics:
    - **Uptime Percentage**: (e.g., 99.9% over 30 days).
    - **Latency Metrics**: P95/P99 response times for dashboards.
    - **Outage Reports**: Correlating multiple alerts into single "events".

---

## 4. Infrastructure Deep Dive

### RabbitMQ (The Backbone)
- **Purpose**: Decouples the *Schedule* from the *Execution* and the *Evaluation* from *Delivery*.
- **How it works**: Symfony Messenger serializes Command objects into JSON/encoded strings and pushes them to RabbitMQ exchanges.
- **Benefits**: If the monitoring load spikes, we simply spin up more worker containers to consume from the `async` queue.

### Redis (The Speed Layer)
- **Telemetry Buffer**: Used as a high-speed staging area for raw check results. This avoids heavy DB write lock contention during monitoring bursts.
- **Monitor State Cache**: Used by `MonitorStateRepository` to quickly fetch the "Previous Health" status without querying the main SQL DB.
- **Distributed Lock/Rate Limiter**: Ensures that if 10 workers are checking the same batch, the "Notification Send" logic is synchronized and rate-limited globally.

---

## 5. Escalation Policy Breakdown

An Escalation Policy is a refined alert that triggers based on sustained downtime.

- **Level 1**: Triggered at 3 failures -> Notify Dev Team (Slack).
- **Level 2**: Triggered at 10 failures -> Notify Team Lead (SMS).
- **Level 3**: Triggered at 50 failures -> Notify CTO (Email + SMS).

The logic resides in `EscalationPolicy::shouldTrigger()`, which ensures the notification is sent **exactly once** when the threshold is crossed, preventing repeated alerts for the same sustained outage.
