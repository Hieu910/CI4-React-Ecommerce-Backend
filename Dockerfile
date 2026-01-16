FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install intl mysqli pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

RUN echo "PassEnv DB_HOST" >> /etc/apache2/apache2.conf && \
    echo "PassEnv DB_PORT" >> /etc/apache2/apache2.conf && \
    echo "PassEnv DB_USER" >> /etc/apache2/apache2.conf && \
    echo "PassEnv DB_PASSWORD" >> /etc/apache2/apache2.conf && \
    echo "PassEnv DB_NAME" >> /etc/apache2/apache2.conf && \
    echo "PassEnv CI_ENVIRONMENT" >> /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/writable

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]