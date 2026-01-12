FROM php:8.3-apache-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    libc-client-dev \
    libkrb5-dev \
    libssl-dev \
    libldap2-dev \
    unzip \
    git \
    cron \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) \
        intl \
        mysqli \
        pdo_mysql \
        gd \
        bcmath \
        mbstring \
        xml \
        zip \
        imap \
        ldap \
        opcache

# Install APCu
RUN pecl install apcu && docker-php-ext-enable apcu

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Install Gettext extension
RUN docker-php-ext-install gettext

# Permissions and entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy the source code into the image
COPY ./osTicket/upload /var/www/html

# If you have these files locally, make sure they are in the folder:
COPY php.ini /usr/local/etc/php/
RUN chown -R www-data:www-data /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]