# Docker Entrypoint Script

## Overview

The `entrypoint.sh` script automatically handles database initialization and migrations when containers start. This ensures your database is always in sync with your code.

## What It Does

On every container startup, the script will:

1. **Wait for Database** - Waits up to 60 seconds for MySQL to be ready
2. **Create Database** - Creates the database if it doesn't exist (idempotent)
3. **Check Migrations** - Detects if there are new Doctrine migrations to run
4. **Run Migrations** - Automatically applies pending migrations
5. **Clear Cache** - Clears Symfony cache for clean state

## Features

### Automatic Migration Detection

The script checks if new migrations exist before running:

```bash
✓ Database is up to date (no new migrations)
# OR
⚠ New migrations detected! Executing...
✓ Migrations completed successfully
```

### Smart Database Creation

- If database doesn't exist → Creates it automatically
- If database exists → Skips creation (safe to run multiple times)

### Color-Coded Output

- 🟢 **Green** - Success
- 🟡 **Yellow** - In progress / Warning
- 🔴 **Red** - Error

## Usage Examples

### Standard Usage (Automatic)

```bash
docker compose up -d
# Containers start, migrations run automatically
```

### Manual Container Restart

```bash
docker compose restart worker
# Worker restarts, script runs migrations if needed
```

### Rebuild with New Code

```bash
docker compose up -d --build
# Containers rebuild, migrations auto-apply
```

## How It Works

```
Container Start
    ↓
Wait for MySQL (max 60s)
    ↓
Check if DB exists → Create if needed
    ↓
Check for new migrations
    ↓
Run migrations if pending
    ↓
Clear cache
    ↓
Start application (scheduler/worker)
```

## Troubleshooting

### Script hangs at "Waiting for database"

**Problem**: Database is not ready
**Solution**: Check database container logs
```bash
docker compose logs database
```

### Migrations fail to apply

**Problem**: Migration has errors or conflicts
**Solution**: Check migration status manually
```bash
docker compose exec worker php bin/console doctrine:migrations:status
```

### Database already exists error

**Problem**: This is normal! The script handles it gracefully.
**Solution**: No action needed. The script will skip creation.

## Environment Variables

The script uses these from `.env.docker`:

- `DATABASE_URL` - MySQL connection string
  - Format: `mysql://root:root@database:3306/uptime_sentinel?serverVersion=8.0`

## Best Practices

1. **Always Add Migrations**: When you change the database schema
   ```bash
   php bin/console doctrine:migrations:generate
   ```

2. **Test Locally First**: Run migrations on local environment before deploying
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

3. **Check Migration Status**: See which migrations have been applied
   ```bash
   docker compose exec worker php bin/console doctrine:migrations:status
   ```

4. **Rollback if Needed**: Rollback the last migration
   ```bash
   docker compose exec worker php bin/console doctrine:migrations:migrate prev
   ```

## Development Workflow

```bash
# 1. Make database changes (create entity, modify schema, etc.)
php bin/console doctrine:migrations:generate

# 2. Test migration locally
php bin/console doctrine:migrations:migrate

# 3. Commit migration file
git add migrations/Version*.php

# 4. Deploy - containers auto-apply migration on next start
docker compose up -d --build
```

## Technical Details

- **Location**: `/Users/anar/Projects/Own/Interviews/uptime-sentinel/entrypoint.sh`
- **Executed**: By scheduler and worker containers (defined in Dockerfile)
- **User**: Runs as `www-data` inside container
- **Timeout**: 60 seconds for database connection
- **Idempotent**: Safe to run multiple times

## Related Files

- `Dockerfile` - Copies and sets entrypoint script
- `compose.yaml` - Defines service configuration
- `.env.docker` - Database connection details
- `migrations/` - Doctrine migration files
