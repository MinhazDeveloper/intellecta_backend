FROM php:8.2-fpm

# ১. প্রয়োজনীয় ডিপেন্ডেন্সি ইন্সটল (PostgreSQL এর জন্য libpq-dev যোগ করা হয়েছে)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    unzip \
    nginx \
    libpq-dev 

# PostgreSQL এর জন্য pdo_pgsql ইন্সটল করা হয়েছে
RUN docker-php-ext-install pdo_pgsql gd

# ২. প্রোজেক্ট ফাইল কপি
WORKDIR /var/www/html
COPY . .

# ৩. Composer ইন্সটল এবং প্যাকেজ ডাউনলোড
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# ৪. Nginx কনফিগারেশন সেটআপ (লারাভেলের জন্য অত্যন্ত জরুরি)
RUN echo 'server { \n\
    listen 80; \n\
    root /var/www/html/public; \n\
    index index.php index.html; \n\
    location / { \n\
        try_files $uri $uri/ /index.php?$query_string; \n\
    } \n\
    location ~ \.php$ { \n\
        include fastcgi_params; \n\
        fastcgi_pass 127.0.0.1:9000; \n\
        fastcgi_index index.php; \n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \n\
    } \n\
}' > /etc/nginx/sites-available/default

# ৫. পারমিশন সেটআপ
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ৬. পোর্ট এক্সপোজ করা
EXPOSE 80

# ৭. কন্টেইনার স্টার্ট কমান্ড
# Render-এ ডাটাবেস কানেকশন সেট হওয়ার পর মাইগ্রেশন রান হবে
CMD php artisan migrate --force && php-fpm -D && nginx -g "daemon off;"