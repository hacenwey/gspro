FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libicu-dev \
        zip \
        unzip \
        git \
        mariadb-server \
        mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        zip \
        gd \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

RUN { \
        echo 'upload_max_filesize = 20M'; \
        echo 'post_max_size = 25M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 120'; \
        echo 'date.timezone = UTC'; \
        echo 'display_errors = Off'; \
        echo 'display_startup_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT'; \
        echo 'error_log = /var/log/apache2/php_errors.log'; \
    } > /usr/local/etc/php/conf.d/app.ini

COPY docker/apache/gestion_commerciale.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html/gestion_commerciale

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts --prefer-dist

COPY . /var/www/html/gestion_commerciale

RUN mkdir -p /var/www/html/gestion_commerciale/public/uploads \
    && chown -R www-data:www-data /var/www/html/gestion_commerciale

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
