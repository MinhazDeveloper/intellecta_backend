FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git unzip nginx
RUN docker-php-ext-install pdo_mysql gd

# Copy project files
COPY . /var/www/html
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Setup Nginx and Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/cache
CMD php artisan migrate --force && service nginx start && php-fpm