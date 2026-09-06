# 本番用イメージ（ECS Fargateで実行）。
# Nginx + PHP-FPM を supervisord で1コンテナにまとめる構成。
# キューワーカー・スケジューラ（infra/terraform/modules/ecs）も同じイメージを使い、
# ECS側のコンテナ定義でコマンドだけ `php artisan queue:work` 等に差し替える。
#
# ビルド（リポジトリルートから）:
#   docker build -f docker/production/Dockerfile -t tms-app:latest .

FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        nginx \
        supervisor \
        postgresql-client \
        libpq-dev \
        icu-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        pcntl \
        bcmath \
        opcache \
    && apk del $PHPIZE_DEPS

# 本番向けPHP設定（OPcache有効化、エラー表示オフ）。
COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/production/nginx.conf /etc/nginx/nginx.conf
COPY docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

COPY --from=composer /app/vendor ./vendor
COPY . .
COPY --from=composer /app/vendor/autoload.php ./vendor/autoload.php

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 80

# 起動時にヘルスチェック用エンドポイント(/up)が確実に応答することを優先し、
# マイグレーションはコンテナ起動時には実行しない（デプロイ手順の中でCI/CDから
# `aws ecs run-task` 等を使い `php artisan migrate --force` を1回だけ明示的に実行する運用とする。
# 複数タスクが同時に起動する構成で、起動のたびにマイグレーションを走らせると競合するため）。
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
