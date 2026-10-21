#!/bin/sh
set -eu

: "${APP_KEY:?Set APP_KEY in Render}"
: "${APP_URL:?Set APP_URL to the Render HTTPS URL}"
: "${DB_HOST:?Set Aiven DB_HOST in Render}"
: "${DB_DATABASE:?Set Aiven DB_DATABASE in Render}"
: "${DB_USERNAME:?Set Aiven DB_USERNAME in Render}"
: "${DB_PASSWORD:?Set Aiven DB_PASSWORD in Render}"

if [ "${DB_CONNECTION:-}" != mysql ]; then
    echo 'DB_CONNECTION must be mysql for the demo.' >&2
    exit 1
fi

if [ ! -s "${MYSQL_ATTR_SSL_CA:-/etc/secrets/aiven-ca.pem}" ]; then
    echo 'Add the Aiven CA certificate as /etc/secrets/aiven-ca.pem in Render.' >&2
    exit 1
fi

php artisan migrate --force

if [ "${SIBK_DEMO_ENABLED:-false}" = true ]; then
    php artisan db:seed --class=DemoSeeder --force
fi

chown -R www-data:www-data bootstrap/cache storage
exec apache2-foreground
