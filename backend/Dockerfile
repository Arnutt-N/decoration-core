FROM php:8.3-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# OPcache for production
RUN echo "opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.max_accelerated_files=10000\n\
opcache.validate_timestamps=0\n\
opcache.revalidate_freq=0" > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Copy source code
COPY . .

# Create uploads directory
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

# PassEnv — Apache must forward env vars to PHP
RUN echo "PassEnv MYSQL_HOST MYSQL_PORT MYSQL_DATABASE MYSQL_USER MYSQL_PASSWORD MYSQL_SSL JWT_SECRET" \
    >> /etc/apache2/conf-enabled/passenv.conf

# UTF-8 encoding for Thai text
RUN printf "AddDefaultCharset UTF-8\n" > /etc/apache2/conf-enabled/charset.conf \
    && printf "default_charset = \"UTF-8\"\n" > /usr/local/etc/php/conf.d/charset.ini

EXPOSE 80
