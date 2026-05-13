#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p \
    bootstrap/cache \
    storage/app/private/octave_sessions \
    storage/app/private/octave_temp \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data bootstrap/cache storage || true

composer install --no-interaction --prefer-dist --optimize-autoloader

if [ -f package-lock.json ]; then
    npm ci --ignore-scripts
else
    npm install --ignore-scripts
fi

npm run build

APP_KEY_VALUE="$(grep -E '^APP_KEY=' .env | head -n 1 | cut -d '=' -f 2- || true)"

if [ -z "${APP_KEY_VALUE}" ]; then
    php artisan key:generate --force
fi

if [ "${DB_CONNECTION:-}" = "mysql" ] || [ "${DB_CONNECTION:-}" = "mariadb" ]; then
    echo "Waiting for database ${DB_HOST:-db}:${DB_PORT:-3306}..."

    for attempt in $(seq 1 60); do
        if php -r '
            $host = getenv("DB_HOST") ?: "db";
            $port = getenv("DB_PORT") ?: "3306";
            $database = getenv("DB_DATABASE") ?: "webte2";
            $username = getenv("DB_USERNAME") ?: "webte2";
            $password = getenv("DB_PASSWORD") ?: "webte2";

            try {
                new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password, [
                    PDO::ATTR_TIMEOUT => 2,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                exit(0);
            } catch (Throwable $exception) {
                exit(1);
            }
        '; then
            break
        fi

        if [ "${attempt}" = "60" ]; then
            echo "Database is not reachable after ${attempt} attempts." >&2
            exit 1
        fi

        sleep 2
    done
fi

php artisan migrate --force
php artisan config:clear

exec "$@"
