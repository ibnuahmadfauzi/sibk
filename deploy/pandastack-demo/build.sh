#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/../.."

if [ "$(id -u)" -eq 0 ]; then
    as_root=()
elif command -v sudo >/dev/null 2>&1 && sudo -n true; then
    as_root=(sudo -n -E)
else
    echo 'PandaStack harus menyediakan root atau sudo tanpa kata sandi untuk memasang PHP.' >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive
"${as_root[@]}" apt-get update
"${as_root[@]}" apt-get install -y --no-install-recommends ca-certificates curl software-properties-common unzip openssl
"${as_root[@]}" add-apt-repository -y ppa:ondrej/php
"${as_root[@]}" apt-get update
"${as_root[@]}" apt-get install -y --no-install-recommends \
    apache2 libapache2-mod-php8.4 php8.4-cli php8.4-curl php8.4-gd \
    php8.4-mbstring php8.4-mysql php8.4-xml php8.4-zip
"${as_root[@]}" a2enmod rewrite headers
if "${as_root[@]}" service apache2 status >/dev/null 2>&1; then "${as_root[@]}" service apache2 stop; fi

mkdir -p .pandastack-bin bootstrap/cache storage/app/private storage/app/public \
    storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

installer=$(mktemp)
trap 'rm -f "$installer"' EXIT
curl -fsSL https://getcomposer.org/installer -o "$installer"
signature=$(curl -fsSL https://composer.github.io/installer.sig)
printf '%s  %s\n' "$signature" "$installer" | sha384sum --check --status
php8.4 "$installer" --quiet --install-dir="$PWD/.pandastack-bin" --filename=composer
if [ "$(id -u)" -eq 0 ]; then export COMPOSER_ALLOW_SUPERUSER=1; fi
php8.4 .pandastack-bin/composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress
php8.4 .pandastack-bin/composer check-platform-reqs --no-dev

npm ci --include=dev --ignore-scripts
npm run build

"${as_root[@]}" chown -R www-data:www-data bootstrap/cache storage
