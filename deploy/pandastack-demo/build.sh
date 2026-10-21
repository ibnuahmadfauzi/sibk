#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

for tool in curl sha256sum sha384sum npm; do
    command -v "$tool" >/dev/null || { echo "Missing build tool: $tool" >&2; exit 1; }
done
test -s /etc/ssl/certs/ca-certificates.crt || { echo 'System CA certificates are missing.' >&2; exit 1; }
export SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

mkdir -p .pandastack-bin bootstrap/cache storage/app/private storage/app/public \
    storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

frankenphp="$PWD/.pandastack-bin/frankenphp"
curl -fL --retry 3 --retry-all-errors --retry-delay 3 --retry-max-time 330 --connect-timeout 15 --max-time 300 \
    https://github.com/php/frankenphp/releases/download/v1.13.1/frankenphp-linux-x86_64-gnu \
    -o "$frankenphp"
printf '%s  %s\n' '1a2341aa187edf68a050cc9fdf37a629160c6ffdb6345a03618bdc4fbf111f89' "$frankenphp" | sha256sum --check --status
chmod 755 "$frankenphp"

"$frankenphp" php-cli -r '
    foreach (["curl", "gd", "mbstring", "pdo_mysql", "xml", "zip", "openssl"] as $extension) {
        if (!extension_loaded($extension)) {
            fwrite(STDERR, "Missing PHP extension: $extension\n");
            exit(1);
        }
    }
'

installer=$(mktemp)
trap 'rm -f "$installer"' EXIT
curl -fL --retry 3 --retry-all-errors --retry-delay 3 --connect-timeout 15 --max-time 60 \
    https://getcomposer.org/installer -o "$installer"
signature=$(curl -fL --retry 3 --retry-all-errors --retry-delay 3 --connect-timeout 15 --max-time 60 \
    https://composer.github.io/installer.sig)
printf '%s  %s\n' "$signature" "$installer" | sha384sum --check --status
"$frankenphp" php-cli "$installer" --quiet --install-dir="$PWD/.pandastack-bin" --filename=composer

if [ "$(id -u)" -eq 0 ]; then export COMPOSER_ALLOW_SUPERUSER=1; fi
"$frankenphp" php-cli .pandastack-bin/composer install --no-dev --no-scripts --no-interaction \
    --prefer-dist --optimize-autoloader --no-progress
"$frankenphp" php-cli artisan package:discover --ansi
"$frankenphp" php-cli .pandastack-bin/composer check-platform-reqs --no-dev

npm ci --include=dev --ignore-scripts
npm run build
