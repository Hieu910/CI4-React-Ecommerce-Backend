FROM php:8.2-apache

# Cài đặt các thư viện hệ thống và PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install intl mysqli pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Cấu hình PHP để nhận biến môi trường tốt hơn
RUN echo "variables_order = \"EGPCS\"" >> /usr/local/etc/php/conf.d/custom.ini

# Kích hoạt module rewrite của Apache
RUN a2enmod rewrite

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Tối ưu hóa việc cài đặt Composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy toàn bộ code vào container
COPY . .

# --- PHẦN SỬA ĐỔI QUAN TRỌNG CHO APACHE ---
# Thay đổi DocumentRoot thành thư mục public của CI4
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Chèn cấu hình Directory vào bên trong file config để chắc chắn nó được nhận
RUN sed -i '/<\/VirtualHost>/i \
    <Directory /var/www/html/public>\n \
        Options Indexes FollowSymLinks\n \
        AllowOverride All\n \
        Require all granted\n \
    </Directory>' /etc/apache2/sites-available/000-default.conf
# -----------------------------------------

# Cấp quyền cho docker-entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Phân quyền chuẩn cho web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/writable

EXPOSE 80

CMD ["/usr/local/bin/docker-entrypoint.sh"]