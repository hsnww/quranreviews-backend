# syntax=docker/dockerfile:1

# 8.2 لأن composer.lock مقفول على lcobucci/clock 3.1 (لا يدعم 8.3)
FROM serversideup/php:8.2-fpm-nginx AS base
USER root
RUN install-php-extensions intl gd zip bcmath exif
WORKDIR /var/www/html

# ---------- التطوير: الكود مربوط من المضيف (bind mount) ----------
FROM base AS development
# مطابقة www-data لمستخدم WSL حتى تكون ملفات storage و vendor قابلة للكتابة من الطرفين
ARG USER_ID=1000
ARG GROUP_ID=1000
RUN docker-php-serversideup-set-id www-data ${USER_ID}:${GROUP_ID} \
 && docker-php-serversideup-set-file-permissions --owner ${USER_ID}:${GROUP_ID} --service nginx
USER www-data

# ---------- الإنتاج: الكود داخل الصورة ----------
FROM base AS production
ENV PHP_OPCACHE_ENABLE=1
USER www-data
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY --chown=www-data:www-data . .
# يشغّل package:discover و filament:upgrade تلقائيًا
RUN composer dump-autoload --optimize --no-interaction
