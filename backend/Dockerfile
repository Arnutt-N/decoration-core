FROM composer:2.7 AS deps
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader

FROM php:8.3-apache
RUN a2enmod rewrite
RUN docker-php-ext-install pdo pdo_mysql

COPY --from=deps /app/vendor /var/www/html/vendor
COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
