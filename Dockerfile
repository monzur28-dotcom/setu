# syntax=docker/dockerfile:1
#
# Setu, containerised for a platform host (Railway, Fly, Render, any VPS).
#
# Apache rather than nginx + php-fpm + supervisor: one process, one config
# file, and nothing to keep in sync. This app is not throughput-bound — it
# is bound by a moderator reading profiles — so the simplest correct server
# is the right one.
#
# GD is installed here, which the laptop this was written on did not have.
# That matters: intervention/image is a real dependency, so on this image
# uploaded photographs can be resized and blurred as rasters instead of
# falling back to the vector placeholders the seeder generates.

FROM php:8.2-apache

# libzip/libpng/libjpeg/freetype are the build inputs for gd and zip.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev libicu-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring bcmath gd zip exif intl opcache \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Opcache settings for a long-running container. `validate_timestamps=0`
# is safe because the code only changes when the image is rebuilt.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Uploads: PhotoIntake caps a photograph at 5 MB and a hero slide at 8 MB.
# PHP has to allow at least that or the cap never gets a chance to apply.
RUN { \
        echo 'upload_max_filesize=12M'; \
        echo 'post_max_size=16M'; \
        echo 'memory_limit=256M'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/setu.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies first, so a code change does not re-resolve the whole tree.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader \
        --prefer-dist --no-interaction --no-progress

COPY . .

# mkdir first: .dockerignore keeps the hero uploads out of the build context,
# so that directory may not exist yet and chown would fail on it.
#
# package:discover writes bootstrap/cache/packages.php. Without it Laravel
# rediscovers its packages on the first request of every cold container.
RUN mkdir -p public/img/hero storage/framework/cache/data bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache public/img/hero

COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

# The platform tells us which port to listen on; 8080 is only the fallback.
ENV PORT=8080
EXPOSE 8080

CMD ["start"]
