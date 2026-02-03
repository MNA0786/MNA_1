FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git unzip curl && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN chmod 777 users.json error.log userbot.session || true

RUN composer install --no-dev --optimize-autoloader

CMD ["php", "index.php"]
