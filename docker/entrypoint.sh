#!/bin/bash
set -euo pipefail

cd /var/www/html

log() { echo "[entrypoint] $*"; }

# -------------------------------------------------------------
# Wait for MySQL. Compose's depends_on only waits for the container
# to start, not for the server to accept connections.
# -------------------------------------------------------------
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
  log "waiting for database at ${DB_HOST:-mysql}:${DB_PORT:-3306} ..."
  for i in $(seq 1 60); do
    if php -r '
      $h=getenv("DB_HOST")?:"mysql"; $p=getenv("DB_PORT")?:"3306";
      $c=@fsockopen($h,(int)$p,$e,$s,2);
      exit($c ? 0 : 1);
    '; then
      log "database is reachable"
      break
    fi
    if [ "$i" = "60" ]; then
      log "ERROR: database unreachable after 120s"
      exit 1
    fi
    sleep 2
  done
fi

# -------------------------------------------------------------
# APP_KEY. Generating one at runtime would produce a *different* key
# on every container, which silently invalidates all existing sessions
# and breaks decryption of anything already stored encrypted.
# Fail loudly instead.
# -------------------------------------------------------------
if [ -z "${APP_KEY:-}" ]; then
  cat <<'EOF'
[entrypoint] FATAL: APP_KEY is not set.

Generate one locally and set it as an environment variable in Dokploy:

    docker run --rm whatsapp-marketing php artisan key:generate --show

It must stay the same across deploys. Changing it logs every user out
and makes previously encrypted data unreadable.
EOF
  exit 1
fi

# -------------------------------------------------------------
# Storage symlink — public/storage is gitignored, recreate per container.
# -------------------------------------------------------------
if [ ! -L public/storage ]; then
  log "creating storage symlink"
  php artisan storage:link --quiet || log "WARN: storage:link failed"
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# -------------------------------------------------------------
# Migrations run only in the web container, so the queue and scheduler
# containers cannot race it during a rolling deploy.
# -------------------------------------------------------------
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  log "running migrations"
  php artisan migrate --force --no-interaction
fi

# -------------------------------------------------------------
# Config caching. Must happen after env vars are present, and must be
# cleared first or a stale cache from build time wins.
# -------------------------------------------------------------
# Skipped during the image build because it needs a live database.
log "discovering packages"
php artisan package:discover --quiet || log "WARN: package:discover failed"

log "caching configuration"
php artisan config:clear --quiet || true
php artisan config:cache --quiet
php artisan route:cache --quiet || log "WARN: route:cache failed (closure routes?)"
php artisan view:cache  --quiet || log "WARN: view:cache failed"

log "ready — starting: $*"
exec "$@"
