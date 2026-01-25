# Monitoring Context - Development Roadmap

This roadmap aligns with the **Learning Path** from `PROJECT_BLUEPRINT.md` (Phase 1: Architecture & System Design).

---

## Phase 1: Core CRUD (Foundation)

### 1.1 CreateMonitor
- [ ] `CreateMonitorCommand` + `CreateMonitorHandler`
- [ ] `MonitorController::create()` - `POST /api/monitors`
- [ ] Request validation (DTO with Symfony Validator)
- [ ] Unit test for handler

### 1.2 GetMonitor / ListMonitors
- [ ] `GetMonitorQuery` + `GetMonitorHandler`
- [ ] `ListMonitorsQuery` + `ListMonitorsHandler` (with pagination)
- [ ] `MonitorController::show()` - `GET /api/monitors/{id}`
- [ ] `MonitorController::index()` - `GET /api/monitors`

### 1.3 UpdateMonitor
- [ ] `UpdateMonitorCommand` + `UpdateMonitorHandler`
- [ ] `MonitorController::update()` - `PUT /api/monitors/{id}`
- [ ] Domain method: `Monitor::updateConfiguration()`

### 1.4 DeleteMonitor
- [ ] `DeleteMonitorCommand` + `DeleteMonitorHandler`
- [ ] `MonitorController::delete()` - `DELETE /api/monitors/{id}`

---

## Phase 2: Domain Behavior (DDD Patterns)

### 2.1 Status Management
- [ ] Domain method: `Monitor::pause()` → set status to `PAUSED`
- [ ] Domain method: `Monitor::resume()` → set status to `ACTIVE`
- [ ] `PauseMonitorCommand` + `PauseMonitorHandler`
- [ ] `ResumeMonitorCommand` + `ResumeMonitorHandler`
- [ ] `MonitorController::pause()` - `PATCH /api/monitors/{id}/pause`
- [ ] `MonitorController::resume()` - `PATCH /api/monitors/{id}/resume`

### 2.2 Check Lifecycle
- [ ] Domain method: `Monitor::markChecked(DateTimeImmutable $at)` → update `lastCheckedAt`, calculate `nextCheckAt`
- [ ] Domain method: `Monitor::isDue()` → check if `nextCheckAt < now()`

---

## Phase 3: Dispatcher Pattern (Case A - Thundering Herd)

### 3.1 Dispatcher Service
- [ ] `DispatchDueMonitorsCommand` (Symfony Console)
- [ ] `MonitorRepository::findDueMonitors()` → monitors where `nextCheckAt <= now()` AND `status = ACTIVE`
- [ ] Dispatch `CheckMonitorJob` messages to RabbitMQ
- [ ] Cron configuration (runs every minute)

### 3.2 Worker Handler
- [ ] `CheckMonitorJob` message class
- [ ] `CheckMonitorJobHandler` (Symfony Messenger)
- [ ] HTTP check logic (Symfony HttpClient)
- [ ] Push results to Redis buffer (for Telemetry context)

---

## Phase 4: Alerting (Optional Enhancement)

### 4.1 Alert Rules
- [ ] `AlertRule` entity (already scaffolded)
- [ ] `CreateAlertRuleCommand` + handler
- [ ] Consecutive failure tracking

---

## File Structure (Target)

```
src/Monitoring/
├── Application/
│   ├── Command/
│   │   ├── CreateMonitor/
│   │   │   ├── CreateMonitorCommand.php
│   │   │   └── CreateMonitorHandler.php
│   │   ├── UpdateMonitor/
│   │   ├── DeleteMonitor/
│   │   ├── PauseMonitor/
│   │   └── ResumeMonitor/
│   ├── Query/
│   │   ├── GetMonitor/
│   │   └── ListMonitors/
│   └── Dto/
│       ├── CreateMonitorRequest.php
│       └── MonitorResponse.php
├── Domain/
│   ├── Model/
│   │   └── Monitor/
│   │       ├── Monitor.php          # Add behavior methods
│   │       ├── MonitorId.php
│   │       ├── MonitorStatus.php
│   │       ├── Url.php
│   │       └── HttpMethod.php
│   ├── Repository/
│   │   └── MonitorRepositoryInterface.php
│   └── Service/
│       └── MonitorDispatcher.php
└── Infrastructure/
    ├── Controller/
    │   └── MonitorController.php
    ├── Persistence/
    │   └── MonitorRepository.php
    └── Messenger/
        ├── CheckMonitorJob.php
        └── CheckMonitorJobHandler.php
```

---

## Priority Order

1. **CreateMonitor** → Need data to test everything else
2. **ListMonitors / GetMonitor** → Verify data is saved
3. **Pause / Resume** → Practice domain behavior
4. **DispatchDueMonitors** → Core system design pattern (Case A)
5. **CheckMonitorJobHandler** → Connect to Telemetry context
