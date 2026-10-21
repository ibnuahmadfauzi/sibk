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

if [ "$(id -u)" -eq 0 ]; then
    as_root=()
elif command -v sudo >/dev/null 2>&1 && sudo -n -E true; then
    as_root=(sudo -n -E)
else
    echo 'Apache requires root or passwordless sudo in PandaStack.' >&2
    exit 1
fi

ca_file="$PWD/storage/app/private/aiven-ca.pem"
"${as_root[@]}" chown -R "$(id -u):$(id -g)" bootstrap/cache storage
umask 022
printf '%s' "$AIVEN_CA_PEM_B64" | base64 --decode > "$ca_file"
openssl x509 -in "$ca_file" -noout >/dev/null
export MYSQL_ATTR_SSL_CA="$ca_file"
unset AIVEN_CA_PEM_B64

php8.4 artisan migrate --force
php8.4 artisan db:seed --class=DemoSeeder --force

"${as_root[@]}" chown -R www-data:www-data bootstrap/cache storage
if "${as_root[@]}" service apache2 status >/dev/null 2>&1; then "${as_root[@]}" service apache2 stop; fi
printf 'Listen %s\n' "$port" | "${as_root[@]}" tee /etc/apache2/ports.conf >/dev/null
cat <<EOF | "${as_root[@]}" tee /etc/apache2/sites-available/000-default.conf >/dev/null
<VirtualHost *:$port>
    DocumentRoot "$PWD/public"
    <Directory "$PWD/public">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog /proc/self/fd/2
    CustomLog /proc/self/fd/1 combined
</VirtualHost>
EOF

exec "${as_root[@]}" /usr/sbin/apache2ctl -D FOREGROUND
