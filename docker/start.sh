#!/usr/bin/env bash
#
# Container entrypoint. Idempotent: it runs on every boot, restart and
# redeploy, so nothing here may assume it is the first time.
set -euo pipefail

: "${PORT:=8080}"
export PORT

echo "==> Setu starting on port ${PORT}"

# Apache reads ${PORT} from the environment in the vhost, but Listen has to
# be told separately and the base image hardcodes 80.
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# ---------------------------------------------------------------- storage
# A mounted volume arrives empty, which would take out the framework cache,
# the session files and the private photo disks. Recreate the tree every
# boot; mkdir -p on an existing directory is a no-op.
mkdir -p \
    storage/app/photos \
    storage/app/connect_photos \
    storage/app/kyc \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    public/img/hero

chown -R www-data:www-data storage bootstrap/cache public/img/hero || true

# ------------------------------------------------------------------- keys
# APP_KEY encrypts mobile numbers at rest. Generating one here on every boot
# would make yesterday's data undecryptable, so refuse to start instead —
# a loud failure now beats silent, permanent data loss later.
if [ -z "${APP_KEY:-}" ]; then
    echo "FATAL: APP_KEY is not set."
    echo "       Generate one with: php artisan key:generate --show"
    echo "       then set it as an environment variable on this service."
    echo "       Do not let this container invent its own: APP_KEY decrypts"
    echo "       stored mobile numbers, and a new one orphans every row."
    exit 1
fi

# --------------------------------------------------------------- database
# docker/db.php works out MySQL or PostgreSQL from DB_URL or DB_CONNECTION,
# so this script does not need to know which one it is talking to. The last
# attempt prints the driver's own error rather than a generic timeout —
# "password authentication failed" and "no route to host" want different
# fixes, and a bare "no database" tells you neither.
echo "==> Waiting for the database"
for i in $(seq 1 30); do
    if php docker/db.php ready >/dev/null 2>&1; then
        echo "    connected"
        break
    fi
    if [ "$i" = "30" ]; then
        echo "FATAL: no database after 60s. The driver said:"
        php docker/db.php ready || true
        exit 1
    fi
    sleep 2
done

echo "==> Migrating"
php artisan migrate --force --no-interaction

# Seed only into an empty database. A redeploy must never duplicate members
# or, worse, reset a real one's profile back to demo data.
USER_COUNT="$(php docker/db.php users 2>/dev/null || echo 0)"

if [ "${USER_COUNT}" = "0" ]; then
    echo "==> Empty database, seeding"
    php artisan db:seed --force --no-interaction
else
    echo "==> ${USER_COUNT} users already present, skipping seed"
fi

# ------------------------------------------------------------------ cache
# Rebuilt on every boot because the environment can change between deploys.
echo "==> Caching config, routes and views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Handing over to Apache"
exec apache2-foreground
