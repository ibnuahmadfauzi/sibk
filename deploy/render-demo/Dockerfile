FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json .npmrc vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci --ignore-scripts && npm run build

FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 gd pdo_mysql zip \
    && a2enmod rewrite headers \
    && (getent group 1000 >/dev/null || groupadd -g 1000 render) \
    && usermod -a -G 1000 www-data \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/\*:80/\*:10000/; s#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' '<Directory /var/www/html/public>' '    AllowOverride All' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/sibk-public.conf \
    && a2enconf sibk-public \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p bootstrap/cache storage/app/private storage/app/public \
        storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress \
    && composer check-platform-reqs --no-dev \
    && chown -R www-data:www-data bootstrap/cache storage

COPY deploy/render-demo/start.sh /usr/local/bin/sibk-start
RUN chmod +x /usr/local/bin/sibk-start

ENV PORT=10000
EXPOSE 10000
CMD ["/usr/local/bin/sibk-start"]
