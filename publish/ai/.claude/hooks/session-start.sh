#!/bin/bash
# Prepares a Claude Code on the web session so tests and Pint can run:
# Composer dependencies, .env, a MySQL 8 server with the databases from .env and
# phpunit.xml, Passport keys and the built frontend assets.
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
    exit 0
fi

cd "$CLAUDE_PROJECT_DIR"

export COMPOSER_ALLOW_SUPERUSER=1

# Packagist dists are GitHub zipballs, which are unreachable here; rebuild them from git first.
php .claude/hooks/composer-cache.php
composer install --no-interaction --no-progress

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --no-interaction
fi

env_value() {
    grep -E "^$1=" .env | tail -n 1 | cut -d= -f2- | tr -d "\"'" || true
}

phpunit_value() {
    local file

    for file in phpunit.xml phpunit.xml.dist; do
        if [ -f "$file" ]; then
            php -r '
                $xml = simplexml_load_file($argv[1]);
                foreach ($xml->xpath("//php/*[@name=\"".$argv[2]."\"]") ?: [] as $node) {
                    echo $node["value"];
                    break;
                }
            ' "$file" "$1" || true

            return
        fi
    done
}

if [ "$(env_value DB_CONNECTION)" = "mysql" ]; then
    if ! command -v mysqld >/dev/null 2>&1; then
        apt-get update -qq || true
        DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mysql-server >/dev/null
    fi

    mkdir -p /var/run/mysqld /etc/mysql/conf.d
    chown mysql:mysql /var/run/mysqld

    if ! mysqladmin ping >/dev/null 2>&1; then
        (mysqld_safe >/dev/null 2>&1 &)

        for _ in $(seq 1 60); do
            mysqladmin ping >/dev/null 2>&1 && break
            sleep 1
        done
    fi

    # Root over TCP without a password, matching the default .env.example.
    if ! mysql -h127.0.0.1 -uroot -e 'select 1' >/dev/null 2>&1; then
        mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';"
    fi

    for database in "$(env_value DB_DATABASE)" "$(phpunit_value DB_DATABASE)"; do
        if [[ "$database" =~ ^[A-Za-z0-9_]+$ ]]; then
            mysql -h127.0.0.1 -uroot -e "CREATE DATABASE IF NOT EXISTS \`$database\`;"
        fi
    done
fi

if grep -q '"laravel/passport"' composer.json && [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --no-interaction
fi

# Views using @vite need public/build/manifest.json. Runs after composer install,
# since the CSS may import Flux and Filament from vendor.
if [ -f package.json ]; then
    npm install --no-audit --no-fund
    npm run build
fi
