# Notification Orchestration - Phase 2

This document outlines the next phase of evolution for the **Uptime Sentinel** notification engine. The goal is to move from basic alerting to a high-signal, low-noise orchestration system.

## Recommended Best Practices

| Strategy | Details | Rationale |
| :--- | :--- | :--- |
| **Exponential Backoff** | Instead of a flat 1h cooldown, alert intervals increase: 5m, 15m, 30m, 1h, 4h. | Provides high urgency at the start of an incident without causing long-term alert fatigue. |
| **Flapping Detection** | Detect monitors that cycle between UP/DOWN more than 3 times in 5 minutes. | Silences "chatter" and replaces multiple failure/recovery alerts with a single "Monitor is Flapping" warning. |
| **Sensitivity Tiers (SLO)** | Group monitors into `Critical`, `High`, and `Standard` tiers with independent thresholds. | Ensures a failure on a Checkout page alerts immediately, while a Dev Doc site waits for sustained downtime. |
| **Resolved Dominance** | Recovery notifications bypass all cooldowns unless the service is currently flapping. | Ensures you are notified immediately when a fix is deployed, regardless of the failure alert cooldown. |
| **Notification Batching** | Aggregate 10+ rapid-fire alerts into a single summary digest (e.g., "15 monitors are DOWN"). | Prevents "mailbox flooding" during massive infrastructure outages. |

## Advanced Evolution Ideas

### 1. Maintenance Windows & Muting
*   **Scheduled Silence**: Ability to define time windows (e.g., Sunday 2 AM - 4 AM) where notifications are suppressed for planned maintenance.
*   **Ad-hoc Muting**: Add "Mute for 1h" links directly in the notification emails to allow engineers to stop alerts while they are actively working on a fix.

### 2. Multi-Channel Escalation Matrix
Instead of using the same channel for everything, route based on priority:
*   **P0 (Critical)**: SMS / PagerDuty / Phone Call.
*   **P1 (High)**: Slack / Microsoft Teams.
*   **P2 (Normal)**: Email.

### 3. Actionable Internal Context
Enhance the notification body with architectural insights:
*   **Correlated Failures**: "3 other monitors in the same AWS Region are also failing."
*   **Direct Links**: Deep-links to the Admin Dashboard and the specific Monitor Telemetry logs.

### 4. Smart Baselines (Anomaly Detection)
Move beyond simple binary (UP/DOWN) checks:
*   **Latent Failures**: Alert if response time is >300% of the 24-hour moving average, even if the status code is still `200 OK`.

---

## Implementation Priority
1. **Resolved Dominance**: (Highest Impact / Lowest Effort)
2. **Exponential Backoff**: (Refining urgency)
3. **Flapping Detection**: (Reducing noise)
4. **Maintenance Windows**: (Utility)
