# Copyright 2021 - 2024 Amanda Sharief
FROM ubuntu:22.04

ENV DATE_TIMEZONE UTC
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    curl \
    git \
    mysql-client \
    nano \
    unzip \
    wget \
    zip \
    php \
    php-apcu \
    php-cli \
    php-common \
    php-curl \
    php-imap \
    php-intl \
    php-json \
    php-mailparse \
    php-mbstring \
    php-mysql \
    php-opcache \
    php-pear \
    php-readline \
    php-soap \
    php-xml \
    php-zip \
    php-dev \
    postgresql-client \
    php-pgsql \
    php-memcached \
    sqlite3 \ 
    php-sqlite3 \
    php-redis \
    php-xdebug \
    cron \
    locales \
 && rm -rf /var/lib/apt/lists/*

# Configure PHP
RUN echo 'apc.enable_cli=1' >>  /etc/php/8.1/cli/php.ini

# Setup project folder
COPY . /var/lightning
RUN chmod -R 0775 /var/lightning
WORKDIR /var/lightning

Setup Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-interaction

# Setup for testing
RUN locale-gen es_ES.UTF-8
RUN locale-gen nl_NL.UTF-8

ENTRYPOINT ["/bin/bash"]