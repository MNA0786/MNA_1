FROM php:8.2-cli

WORKDIR /app

# System deps
RUN apt-get update && apt-get install -y \
    git unzip curl && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Files copy
COPY . .

# Permissions
RUN chmod 777 users.json error.log userbot.session || true

# Install deps
RUN composer install --no-dev --optimize-autoloader

EXPOSE 10000

CMD ["php", "index.php"]
