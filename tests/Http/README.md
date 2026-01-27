# HTTP Tests - PhpStorm REST Client

This directory contains HTTP test files for testing UptimeSentinel APIs using PhpStorm's built-in REST Client (or VS Code with REST Client extension).

## Prerequisites

1. **Run migrations:**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

2. **Start Symfony server:**
   ```bash
   php bin/console server:run
   ```

3. **Configure base URL** in each `.http` file:
   ```http
   @baseUrl = http://localhost:8000
   ```

## Test Files

### monitors.http
Tests for monitor management endpoints:
- Create monitors (minimal, full config, with headers)
- List and retrieve monitors
- Update monitors
- Pause/resume monitors
- Delete monitors

**Usage:**
1. Create a monitor first
2. Copy the `monitorId` from the response
3. Use it in alert rules and escalation policies tests

### alert-rules.http
Tests for alert rule management endpoints:
- Create alert rules (Email, Slack, Webhook)
- Configure notification types (failure, recovery, both)
- Set cooldown intervals for rate limiting
- Update, enable, disable, and delete rules

**Workflow:**
1. Replace `{{monitorId}}` with an actual monitor ID
2. Create alert rules for the monitor
3. Use the returned `alertRuleId` for update/delete tests

### escalation-policies.http
Tests for escalation policy management endpoints:
- Create global policies (apply to all monitors)
- Create monitor-specific policies
- Multi-level escalation chains
- Enable, disable, and delete policies

**Workflow:**
1. Create global escalation policies first
2. Optionally create monitor-specific overrides
3. View applicable policies for a monitor

## Features

### Variable Substitution
Each file uses variables for repeated values:
```http
@baseUrl = http://localhost:8000
@contentType = application/json
@monitorId = {{monitorId}}
```

Replace `{{monitorId}}` with actual IDs from API responses.

### Named Requests
Requests are named for easy execution:
```http
# @name createMinimalMonitor
POST {{baseUrl}}/api/monitors
```

Click the green play button next to the name in PhpStorm to execute.

### Response Handling
PhpStorm stores responses and allows you to:
- View response status and body
- Copy response values (like IDs)
- Compare responses
- View response time

## Common Workflows

### 1. Setup Complete Monitoring

```http
# Step 1: Create a monitor
POST {{baseUrl}}/api/monitors
Content-Type: application/json

{
    "name": "Production API",
    "url": "https://api.example.com/health",
    "method": "GET",
    "intervalSeconds": 60,
    "timeoutSeconds": 10,
    "expectedStatusCode": 200
}

###

# Step 2: Create alert rules
POST {{baseUrl}}/api/alert-rules
Content-Type: application/json

{
    "monitorId": "paste_monitor_id_here",
    "channel": "email",
    "target": "alerts@example.com",
    "failureThreshold": 3,
    "type": "both",
    "cooldownInterval": "PT1H"
}

###

# Step 3: Create escalation policies
POST {{baseUrl}}/api/escalation-policies
Content-Type: application/json

{
    "monitorId": null,
    "level": 1,
    "consecutiveFailures": 3,
    "channel": "email",
    "target": "level1@example.com"
}
```

### 2. Test Escalation Chain

```http
# Create 3-level escalation
# Level 1: 3 failures → Email
POST {{baseUrl}}/api/escalation-policies
Content-Type: application/json

{
    "monitorId": "paste_monitor_id_here",
    "level": 1,
    "consecutiveFailures": 3,
    "channel": "email",
    "target": "team@example.com"
}

###

# Level 2: 5 failures → Slack
POST {{baseUrl}}/api/escalation-policies
Content-Type: application/json

{
    "monitorId": "paste_monitor_id_here",
    "level": 2,
    "consecutiveFailures": 5,
    "channel": "slack",
    "target": "https://hooks.slack.com/services/..."
}

###

# Level 3: 10 failures → Webhook (Critical)
POST {{baseUrl}}/api/escalation-policies
Content-Type: application/json

{
    "monitorId": "paste_monitor_id_here",
    "level": 3,
    "consecutiveFailures": 10,
    "channel": "webhook",
    "target": "https://pagerduty.example.com/webhook"
}
```

## Debugging

Add `XDEBUG_SESSION_START=1` to any request for step debugging:
```http
POST {{baseUrl}}/api/monitors?XDEBUG_SESSION_START=1
Content-Type: application/json

{
    "name": "Debug Monitor"
}
```

## Expected Responses

### Success (202 Accepted - Async Commands)
```json
{
    "message": "Alert rule creation request accepted"
}
```

### Error (422 Validation Error)
```json
{
    "error": "Validation failed",
    "message": "This value is not valid.",
    "violations": [...]
}
```

### Error (404 Not Found)
```json
{
    "error": "Monitor not found",
    "message": "Monitor with ID \"...\" does not exist"
}
```

## Testing Tips

1. **Use environment files** - Copy `config.json` and customize for different environments (dev, staging, prod)

2. **Chain IDs** - Copy IDs from responses and paste them into subsequent requests

3. **Test error cases** - Each file includes invalid input tests for validation

4. **Use named requests** - Click the green play button in PhpStorm to execute

5. **View responses** - PhpStorm shows response time, status, and body in a split pane

## cleanup

To reset test data:
```bash
# Drop and recreate database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Or use fixtures (if available)
php bin/console doctrine:fixtures:load
```

## Additional Resources

- [PhpStorm REST Client Documentation](https://www.jetbrains.com/help/phpstorm/http-client-in-product-code-editor.html)
- [VS Code REST Client Extension](https://marketplace.visualstudio.com/items?itemName=humao.rest-client)
- [HTTP Request Syntax Reference](https://www.jetbrains.com/help/phpstorm/http-client-reference.html)
