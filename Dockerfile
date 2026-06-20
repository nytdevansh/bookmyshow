FROM php:8.2-apache

# Install PostgreSQL PDO extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite
RUN a2enmod rewrite

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Set proper permissions for uploads directory
RUN mkdir -p /var/www/html/assets/uploads/posters \
    && mkdir -p /var/www/html/assets/uploads/banners \
    && chown -R www-data:www-data /var/www/html/assets \
    && chmod -R 755 /var/www/html/assets

EXPOSE 80
