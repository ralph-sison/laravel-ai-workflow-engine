FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    autoconf \
    bash \
    curl \
    g++ \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    make \
    oniguruma-dev \
    postgresql-dev \
    shadow \
    supervisor \
    unzip \
    zip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        mbstring \
        opcache \
        pcntl \
        pdo \
        pdo_pgsql \
        zip

# Install Redis extension via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create non-root user matching host UID for volume permissions
ARG UID=1000
ARG GID=1000
RUN groupmod -g ${GID} www-data && usermod -u ${UID} -g www-data www-data

WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Install PHP dependencies (skip dev in production via target stage)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000

CMD ["php-fpm"]
