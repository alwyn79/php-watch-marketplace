FROM php:8.1-apache

# -----------------------------------------
# Working directory
# -----------------------------------------
WORKDIR /var/www/html

# -----------------------------------------
# System packages + PHP extensions
# -----------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    netcat-openbsd \
    && docker-php-ext-install zip pdo pdo_mysql mysqli

# -----------------------------------------
# Enable Apache rewrite (.htaccess support)
# -----------------------------------------
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# -----------------------------------------
# Install Composer
# -----------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -----------------------------------------
# Copy project files
# -----------------------------------------
COPY . .

# -----------------------------------------
# Install PHP dependencies
# -----------------------------------------
RUN composer install --no-interaction --prefer-dist

# -----------------------------------------
# Set Apache document root to public
# -----------------------------------------
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# -----------------------------------------
# Expose port
# -----------------------------------------
EXPOSE 80

# -----------------------------------------
# Start sequence
# 1) Wait for MySQL
# 2) Seed products
# 3) Start Apache
# -----------------------------------------
CMD ["bash", "-c", "until nc -z db 3306; do echo 'Waiting for MySQL...'; sleep 2; done; php /var/www/html/seed_products.php; apache2-foreground"]
