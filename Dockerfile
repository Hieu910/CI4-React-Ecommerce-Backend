FROM php:8.2-apache

# 1. Cài đặt các thư viện hệ thống và PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install intl mysqli pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Cấu hình PHP (Variables_order EGPCS giúp đọc biến môi trường Render cực chuẩn)
RUN echo "variables_order = \"EGPCS\"" >> /usr/local/etc/php/conf.d/custom.ini

# 3. Bật module rewrite ngay từ đầu
RUN a2enmod rewrite

# 4. Cấu hình Apache (Dùng cách viết đè file để đảm bảo không sai lệch do khoảng trắng hay ký tự lạ)
RUN printf "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# 5. Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 6. Copy file composer trước (Tận dụng Docker Cache để build nhanh hơn 80%)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# 7. Copy toàn bộ code vào sau
COPY . .

# 8. Cấu hình file entrypoint và phân quyền
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Cấp quyền cho user của Apache (www-data)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/writable

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]