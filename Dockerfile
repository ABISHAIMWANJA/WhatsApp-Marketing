# =============================================================
#  WhatsApp Marketing — Laravel 12 / PHP 8.3
#  Single image serving nginx + PHP-FPM under supervisor.
#  Also used for the queue worker and scheduler (different CMD).
# =============================================================

# ---------- Stage 1: build vendor/ ----------
FROM php:8.3-cli-alpine AS vendor

RUN apk add --no-cache git unzip icu-dev libzip-dev $PHPIZE_DEPS \
 && docker-php-ext-install -j$(nproc) intl zip bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./

# This project ships without a composer.lock. Use `install` when one is
# present (reproducible), fall back to `update` when it is not.
RUN if [ -f composer.lock ]; then \
      composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction; \
    else \
      echo ">> No composer.lock found — resolving fresh (commit the generated lock)"; \
      composer update --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction; \
    fi

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------- Stage 2: runtime ----------
FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache \
      nginx supervisor bash curl tzdata \
      icu-libs libzip libpng libjpeg-turbo freetype \
 && apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
      pdo_mysql mysqli mbstring exif pcntl bcmath gd intl zip opcache \
 && apk del .build-deps

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html

COPY docker/php.ini        /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx.conf     /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh  /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Laravel needs to write here; nginx and php-fpm both run as www-data.
RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && mkdir -p /run/nginx

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
  CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
