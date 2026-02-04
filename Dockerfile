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

# Install PHP Extension Installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install PHP extensions
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

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-scripts --no-interaction --no-dev --optimize-autoloader
RUN php bin/console importmap:install

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
