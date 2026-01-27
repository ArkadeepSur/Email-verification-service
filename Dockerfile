FROM php:8.5-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Create necessary directories and set permissions
RUN mkdir -p /app/storage/logs /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Generate APP_KEY if needed
RUN if [ ! -f .env ]; then cp .env.example .env; fi && \
    php artisan key:generate --force

# Create entrypoint script
RUN echo '#!/bin/sh\nset -e\nphp artisan migrate --force\nphp -S 0.0.0.0:${PORT:-8000} -t public' > /app/entrypoint.sh && \
    chmod +x /app/entrypoint.sh

# Expose port
EXPOSE 8000

# Start with migrations then PHP server
CMD ["/app/entrypoint.sh"]
