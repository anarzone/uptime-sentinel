# Stage 1: AMD64 Builder for native dependency installation
FROM --platform=$BUILDPLATFORM php:8.4-cli-alpine AS builder

WORKDIR /var/www

ENV APP_ENV=prod
ENV APP_DEBUG=0

# Install system dependencies for Composer
RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    zlib-dev \
    linux-headers \
    rabbitmq-c-dev \
    libxslt-dev

# Install PHP extensions required for Symfony boot/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl zip amqp xsl

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install dependencies natively on AMD64 (fast)
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-scripts --no-interaction --no-dev --optimize-autoloader

# Install AssetMapper dependencies and compile assets
COPY . .
RUN php bin/console importmap:install
RUN php bin/console asset-map:compile


# Stage 2: Final ARM64 runtime image
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    rabbitmq-c-dev \
    libpng-dev \
    libxml2-dev \
    icu-dev \
    zlib-dev \
    linux-headers \
    netcat-openbsd

# Install PHP extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_mysql \
    amqp \
    redis \
    pcntl \
    intl \
    zip \
    opcache \
    xsl

# Set working directory
WORKDIR /var/www

# Cache bust - add build arg to invalidate COPY cache
ARG CACHE_BUST=2026-02-12-1
RUN echo "Build: $CACHE_BUST"

# Copy application files
# Copy application files from builder (ensures fresh code, avoids stale build context cache)
COPY --from=builder /var/www .

# Create public_source backup (preserving existing logic)
COPY --from=builder /var/www/public ./public_source

# Optimizations
ENV SYMFONY_ENV=prod
ENV APP_ENV=prod

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]

# Default command
CMD ["php", "bin/console", "messenger:consume", "async", "-vv"]
