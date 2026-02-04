#!/bin/sh
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() { echo "${YELLOW}$1${NC}"; }
log_success() { echo "${GREEN}✓ $1${NC}"; }
log_error() { echo "${RED}✗ $1${NC}"; }

echo "🚀 Starting UptimeSentinel container..."

# Fallback: support both REDIS_URL and REDIS_DSN
if [ -z "$REDIS_URL" ] && [ -n "$REDIS_DSN" ]; then
    export REDIS_URL="$REDIS_DSN"
fi

# Fail fast if required environment variables are missing
check_env() {
    if [ -z "$DATABASE_URL" ]; then log_error "DATABASE_URL is not set"; exit 1; fi
    if [ -z "$REDIS_URL" ]; then log_error "REDIS_URL is not set"; exit 1; fi
    if [ -z "$APP_SECRET" ] || [ "$APP_SECRET" = "changeme" ]; then
        log_error "APP_SECRET is not set or insecure. Please set a random string in Coolify."
        exit 1
    fi
    log_success "Environment variables verified"
}
check_env

wait_for_database() {
    log_info "⏳ Waiting for database to be ready..."
    
    max_tries=30
    try=0

    while [ $try -lt $max_tries ]; do
        # NOTE: dbal:run-sql (not doctrine:query:sql) required for Doctrine DBAL 4.x compatibility
        if php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
            log_success "Database is ready!"
            return 0
        fi

        try=$((try+1))
        echo "  Attempt ${try}/${max_tries}..."
        sleep 2
    done

    log_error "Database connection failed after ${max_tries} attempts"
    exit 1
}

# Create database if needed
create_database_if_needed() {
    log_info "🔍 Ensuring database exists..."
    # || true ensures script continues even if database already exists (expected behavior)
    php bin/console doctrine:database:create --if-not-exists > /dev/null 2>&1 || true
    log_success "Database ready"
}

# Run migrations
run_migrations() {
    log_info "🔄 Running database migrations..."

    # --allow-no-migration: Makes command idempotent (safe when no migrations pending)
    # --no-interaction: Required for automated execution in containers
    if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
        log_success "Migrations completed"
    else
        log_error "Migration execution failed"
        return 1
    fi
}

# Clear and warm cache with lock to prevent race conditions
prepare_cache() {
    log_info "🧹 Preparing cache..."

    # Lock required: Multiple containers (scheduler + workers) start simultaneously
    # Without locking, race conditions cause cache corruption when clearing simultaneously
    LOCK_FILE="/tmp/uptime_cache.lock"

    # Try to acquire lock (with timeout)
    tries=0
    max_tries=60
    while [ $tries -lt $max_tries ]; do
        if mkdir "$LOCK_FILE" 2>/dev/null; then
            # We got the lock, clear cache
            trap "rmdir $LOCK_FILE 2>/dev/null" EXIT
            php bin/console cache:clear --no-warmup 2>/dev/null || true
            php bin/console cache:warmup 2>/dev/null || true
            rmdir "$LOCK_FILE" 2>/dev/null || true
            trap - EXIT
            log_success "Cache ready"
            return 0
        fi
        # Another process has the lock, wait
        tries=$((tries+1))
        [ $((tries % 10)) -eq 0 ] && echo "  Still waiting for cache lock (${tries}/${max_tries})..."
        sleep 1
    done
    
    # Lock timed out, try anyway (cache might already be ready)
    log_info "Cache lock timeout, proceeding anyway..."
}

# Ensure correct permissions
fix_permissions() {
    log_info "🔑 Ensuring directory permissions..."
    # Always ensure www-data owns the writable directories
    chown -R www-data:www-data var/
    log_success "Permissions verified"
}

# Install assets (required for correct Nginx serving when using volumes)
install_assets() {
    log_info "🎨 Installing assets..."
    php bin/console assets:install public --no-interaction > /dev/null 2>&1 || true
    log_success "Assets ready"
}

# Setup messenger transports (ensure exchanges/queues exist in RabbitMQ)
setup_transports() {
    log_info "📬 Setting up messenger transports..."
    php bin/console messenger:setup-transports --no-interaction > /dev/null 2>&1 || true
    log_success "Transports ready"
}

# Main execution flow
main() {
    wait_for_database
    
    # Only the "Primary" container (php-fpm) handles the heavy DB/Asset setup
    if [ "$1" = "php-fpm" ]; then
        log_info "🚀 Primary container detected. Running initialization..."
        create_database_if_needed
        run_migrations
        prepare_cache
        install_assets
        setup_transports
    else
        log_info "⚙️ Service container detected. Waiting for system readiness..."
        prepare_cache # This will just wait for the lock or skip if already done
    fi

    fix_permissions # ALWAYS run this at the end

    log_success "Initialization complete! Starting: $@"

    # exec replaces shell process with PHP - ensures signals (SIGTERM/SIGINT) reach application
    exec "$@"
}

main "$@"
