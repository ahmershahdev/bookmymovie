# syntax=docker/dockerfile:1.7

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund
COPY . .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/cache composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM php:8.3-apache AS app
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev libicu-dev libzip-dev unzip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd intl pdo_mysql opcache zip bcmath pcntl \
 && pecl install redis && docker-php-ext-enable redis \
 && a2enmod rewrite headers expires deflate \
 && apt-get purge -y --auto-remove && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && printf 'ServerTokens Prod\nServerSignature Off\nServerName localhost\n' > /etc/apache2/conf-enabled/zz-hardening.conf \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY deploy/php/opcache.ini /usr/local/etc/php/conf.d/99-bookmymovie.ini
RUN sed -i 's/^zend_extension=opcache/; opcache is enabled by docker-php-ext-install/' /usr/local/etc/php/conf.d/99-bookmymovie.ini

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
COPY deploy/docker/entrypoint.sh /usr/local/bin/entrypoint
RUN rm -f bootstrap/cache/*.php && php artisan package:discover --ansi
RUN chmod +x /usr/local/bin/entrypoint && mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
