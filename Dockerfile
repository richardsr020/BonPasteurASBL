FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    icu-libs icu-dev libzip-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    oniguruma-dev libxml2-dev postgresql-dev git unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql intl zip gd opcache fileinfo mbstring xml bcmath exif \
    && apk del .build-deps \
    && mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/public/uploads \
       /var/www/html/var/storage/archives /var/www/html/var/backups \
    && chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

FROM base AS assets
RUN apk add --no-cache nodejs npm
COPY package.json package-lock.json* tailwind.config.js ./
COPY assets/styles/app.css ./assets/styles/app.css
RUN npm install --no-audit --no-fund && npm run build

FROM base AS dev
RUN composer global require symfony/cli:^5 --no-interaction
ENV PATH="/root/.composer/vendor/bin:${PATH}"

FROM base AS prod
COPY --from=assets /var/www/html/assets/styles/app.compiled.css /var/www/html/assets/styles/app.compiled.css
COPY . /var/www/html
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts \
    && composer dump-autoload --optimize --classmap-authoritative \
    && php bin/console cache:clear --env=prod --no-warmup \
    && php bin/console cache:warmup --env=prod \
    && chown -R www-data:www-data var public/uploads

WORKDIR /var/www/html
EXPOSE 9000
CMD ["php-fpm"]
