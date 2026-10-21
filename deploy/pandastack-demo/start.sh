#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

: "${APP_KEY:?Set APP_KEY in PandaStack}"
: "${APP_URL:?Set the PandaStack HTTPS URL as APP_URL}"
: "${DB_HOST:?Set Aiven DB_HOST in PandaStack}"
: "${DB_PORT:?Set Aiven DB_PORT in PandaStack}"
: "${DB_DATABASE:?Set Aiven DB_DATABASE in PandaStack}"
: "${DB_USERNAME:?Set Aiven DB_USERNAME in PandaStack}"
: "${DB_PASSWORD:?Set Aiven DB_PASSWORD in PandaStack}"
: "${AIVEN_CA_PEM_B64:?Set the base64-encoded Aiven CA certificate in PandaStack}"
: "${SIBK_DEMO_ACCOUNT_PASSWORD:?Set a demo account password in PandaStack}"

if [ "${APP_ENV:-}" != production ] || [ "${APP_DEBUG:-}" != false ] || \
   [ "${DB_CONNECTION:-}" != mysql ] || [ "${SIBK_DEMO_ENABLED:-}" != true ]; then
    echo 'Demo requires APP_ENV=production, APP_DEBUG=false, DB_CONNECTION=mysql, and SIBK_DEMO_ENABLED=true.' >&2
    exit 1
fi

case "$APP_URL" in
    https://*) ;;
    *) echo 'APP_URL must use HTTPS.' >&2; exit 1 ;;
esac

port=${PORT:-3000}
case "$port" in
    ''|*[!0-9]*) echo 'PORT must be a number.' >&2; exit 1 ;;
esac
if [ "$port" -lt 1 ] || [ "$port" -gt 65535 ]; then
    echo 'PORT must be between 1 and 65535.' >&2
    exit 1
fi

test -s /etc/ssl/certs/ca-certificates.crt || { echo 'System CA certificates are missing.' >&2; exit 1; }
export SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt
frankenphp="$PWD/.pandastack-bin/frankenphp"
test -x "$frankenphp" || { echo 'FrankenPHP build artifact is missing.' >&2; exit 1; }

ca_file="$PWD/storage/app/private/aiven-ca.pem"
umask 077
printf '%s' "$AIVEN_CA_PEM_B64" | base64 --decode > "$ca_file"
"$frankenphp" php-cli -r '
    if (@openssl_x509_read(file_get_contents($argv[1])) === false) {
        fwrite(STDERR, "Invalid Aiven CA certificate.\n");
        exit(1);
    }
' "$ca_file"
export MYSQL_ATTR_SSL_CA="$ca_file"
unset AIVEN_CA_PEM_B64

"$frankenphp" php-cli artisan migrate --force
"$frankenphp" php-cli artisan db:seed --class=DemoSeeder --force

exec "$frankenphp" php-server --listen ":${port}" --root "$PWD/public"
