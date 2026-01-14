# Docker Setup for UptimeSentinel

This document explains how to use Docker for infrastructure services while running the Symfony application locally.

## Prerequisites

- Docker Desktop 4.0+ (or Docker Engine 20.10+)
- Docker Compose 2.0+
- PHP 8.4 installed locally (via Herd, Homebrew, or PHP built-in server)
- Composer 2.x+

## Technology Stack

- **Local PHP**: 8.4 (via Herd or CLI)
- **Symfony**: 8.0
- **MySQL**: 8.0 (Docker)
- **RabbitMQ**: 3.x (Docker, with Management Plugin)
- **Redis**: 7 (Docker)

## Quick Start

### 1. Start Infrastructure Services

```bash
# Start MySQL, RabbitMQ, and Redis
docker compose up -d

# View logs
docker compose logs -f

# Stop all services
docker compose down
```

### 2. Configure Environment

Update your `.env` file to connect to Docker services:

```bash
# Database (connects to MySQL on localhost:3306)
DATABASE_URL="mysql://root:root@localhost:3306/uptime_sentinel?serverVersion=8.0&charset=utf8mb4"

# RabbitMQ (connects to localhost:5672)
MESSENGER_TRANSPORT_DSN="amqp://user:password@localhost:5672/%2fmessages"

# Redis (connects to localhost:6379)
REDIS_DSN="redis://localhost:6379"
```

### 3. Run Symfony Application Locally

```bash
# Install dependencies
composer install

# Run database migrations
php bin/console doctrine:migrations:migrate

# Start Symfony web server (or use Herd)
symfony server:start

# In another terminal, run worker processes
php bin/console messenger:consume async
```

## Services Overview

| Service | Container Name | Ports Exposed | Purpose |
|---------|---------------|---------------|---------|
| **database** | uptime_mysql | 3306 → 3306 | MySQL 8.0 database |
| **rabbitmq** | uptime_rabbitmq | 5672, 15672 | Message queue + Management UI |
| **redis** | uptime_redis | 6379 → 6379 | Cache and write buffer |

## Development Workflow

### Database Operations

```bash
# Run migrations
php bin/console doctrine:migrations:migrate

# Create a new migration
php bin/console doctrine:migrations:generate

# Query the database directly
docker exec -it uptime_mysql mysql -u root -proot uptime_sentinel
```

### RabbitMQ Management

Access the RabbitMQ Management UI at http://localhost:15672 (user/password)

### Viewing Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f database
docker compose logs -f rabbitmq
docker compose logs -f redis

# Last 100 lines
docker compose logs --tail=100 database
```

### Running Workers

```bash
# Single worker process
php bin/console messenger:consume async

# Multiple worker processes (run in separate terminals)
php bin/console messenger:consume async --time-limit=3600 --memory-limit=256M
```

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker compose logs database

# Check if all services are running
docker compose ps

# Rebuild from scratch
docker compose down
docker compose up -d
```

### Database Connection Issues

```bash
# Check if database is running
docker compose ps database

# Connect to database
docker exec -it uptime_mysql mysql -u root -proot uptime_sentinel

# Verify database exists
docker exec uptime_mysql mysql -u root -proot -e "SHOW DATABASES;"
```

### RabbitMQ Connection Issues

```bash
# Check RabbitMQ status
docker compose logs rabbitmq

# Access management UI
open http://localhost:15672
```

### Reset Everything

```bash
# Stop and remove all containers and volumes
docker compose down -v

# Start fresh
docker compose up -d
```

## Useful Commands Reference

```bash
# Start services in background
docker compose up -d

# Start services in foreground (to see logs)
docker compose up

# Stop services
docker compose stop

# Stop and remove containers
docker compose down

# Stop and remove everything including volumes
docker compose down -v

# View running containers
docker compose ps

# Execute command in container
docker compose exec <service> <command>

# View logs
docker compose logs -f <service>

# Show resource usage
docker compose top
```

## Architecture Notes

This simplified setup runs **PHP/Symfony locally** while using Docker only for infrastructure services:

- **PHP application**: Runs on your host machine (via Herd, Symfony CLI, or built-in server)
- **MySQL**: Runs in Docker on port 3306
- **RabbitMQ**: Runs in Docker on ports 5672 (AMQP) and 15672 (Management UI)
- **Redis**: Runs in Docker on port 6379

This approach gives you:
- Faster development (no container rebuilds needed)
- Easy debugging (full access to PHP code and Xdebug)
- Production-like infrastructure (MySQL, RabbitMQ, Redis)
- Simple worker scaling (just run multiple processes locally)

## Next Steps

1. Review the [PROJECT_BLUEPRINT.md](PROJECT_BLUEPRINT.md) for architecture details
2. Check [composer.json](composer.json) for installed dependencies
3. Explore the `src/` directory to understand the code structure
4. Run your first database migration: `php bin/console doctrine:migrations:migrate`
