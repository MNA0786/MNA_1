FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    curl \
    && docker-php-ext-install mysqli zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache configuration
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN a2enmod rewrite headers

# Set document root
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create necessary directories and files
RUN mkdir -p backups \
    && touch movies.csv users.json bot_stats.json error.log forward_logs.txt request_logs.txt \
    && chmod 666 movies.csv users.json bot_stats.json error.log forward_logs.txt request_logs.txt

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]
