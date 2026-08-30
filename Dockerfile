FROM php:8.3-apache

# 系统依赖 + PHP 扩展
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
        cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_sqlite zip gd bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# Apache mod_rewrite（规格 §7 URL 重写）
RUN a2enmod rewrite headers expires

# 复制项目
COPY . /var/www/html/

# 权限：storage 与 bootstrap/cache 可写
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache

# Apache DocumentRoot 指向 public（vhost 覆盖）
RUN printf '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

# Cron：每分钟跑调度器（规格 §13；也可用外部调度器打 /cron?key=）
RUN printf '* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1\n' \
        > /etc/cron.d/monit \
    && chmod 0644 /etc/cron.d/monit \
    && crontab /etc/cron.d/monit

EXPOSE 80

# 启动：cron 后台 + Apache 前台
CMD cron && apache2-foreground
