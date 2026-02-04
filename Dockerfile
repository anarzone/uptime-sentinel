# Stage 1: AMD64 Builder for native dependency installation
FROM --platform=$BUILDPLATFORM php:8.4-cli-alpine AS builder

WORKDIR /var/www

# Install system dependencies for Composer
RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    zlib-dev \
    linux-headers

# Install PHP extensions required for Symfony boot/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl zip

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install dependencies natively on AMD64 (fast)
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-scripts --no-interaction --no-dev --optimize-autoloader

# Install AssetMapper dependencies
COPY . .
RUN php bin/console importmap:install


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
    linux-headers

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

# Copy application files
COPY . .

# Copy pre-built dependencies from builder
COPY --from=builder /var/www/vendor ./vendor
COPY --from=builder /var/www/assets/vendor ./assets/vendor

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
