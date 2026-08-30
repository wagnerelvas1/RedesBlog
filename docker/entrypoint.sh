#!/bin/bash
set -e

mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache
chmod -R ug+rw storage bootstrap/cache || true

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

exec "$@"
