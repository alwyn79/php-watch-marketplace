FROM php:8.1-apache

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    netcat-openbsd \
    && docker-php-ext-install zip pdo pdo_mysql mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod status

# Set DocumentRoot to /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# Apache metrics config
RUN echo "ExtendedStatus On" >> /etc/apache2/apache2.conf
RUN echo "Listen 8080" >> /etc/apache2/ports.conf

# Apache server-status on port 8080
RUN echo "<VirtualHost *:8080>" > /etc/apache2/sites-available/status.conf && \
    echo "    <Location />" >> /etc/apache2/sites-available/status.conf && \
    echo "        SetHandler server-status" >> /etc/apache2/sites-available/status.conf && \
    echo "        Require all granted" >> /etc/apache2/sites-available/status.conf && \
    echo "    </Location>" >> /etc/apache2/sites-available/status.conf && \
    echo "</VirtualHost>" >> /etc/apache2/sites-available/status.conf && \
    a2ensite status

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install base PHP dependencies from composer.json
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Install OpenTelemetry packages + required PSR HTTP client/transport deps
RUN composer require \
    open-telemetry/api \
    open-telemetry/sdk \
    open-telemetry/exporter-otlp \
    open-telemetry/sem-conv \
    php-http/guzzle7-adapter \
    guzzlehttp/psr7 \
    --no-interaction --optimize-autoloader

# OpenTelemetry Environment Variables
ENV OTEL_SERVICE_NAME=php-watch
ENV OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
ENV OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf

EXPOSE 80
EXPOSE 8080

CMD ["bash", "-c", "\
    echo 'Waiting for MySQL...'; \
    until nc -z mysql 3306; do sleep 2; done; \
    echo 'MySQL is up!'; \
    php /var/www/html/seed_products.php || true; \
    apache2-foreground"]