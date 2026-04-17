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
    } > /usr/local/etc/php/conf.d/app.ini

COPY docker/apache/gestion_commerciale.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html/gestion_commerciale

COPY . /var/www/html/gestion_commerciale

RUN mkdir -p /var/www/html/gestion_commerciale/public/uploads \
    && chown -R www-data:www-data /var/www/html/gestion_commerciale

EXPOSE 80
