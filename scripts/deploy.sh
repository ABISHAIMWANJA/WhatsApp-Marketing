#!/usr/bin/env bash
#
#  One-shot deploy for whatsapp.bomalogic.com
#
#      git clone https://github.com/ABISHAIMWANJA/WhatsApp-Marketing.git /opt/whatsapp-marketing
#      cd /opt/whatsapp-marketing && ./scripts/deploy.sh
#
#  Generates secrets, writes .env, and brings the stack up. Safe to
#  re-run: an existing .env is never overwritten.
#
set -euo pipefail

cd "$(dirname "$0")/.."

BOLD=$'\e[1m'; RED=$'\e[31m'; GRN=$'\e[32m'; YLW=$'\e[33m'; OFF=$'\e[0m'
say()  { echo "${BOLD}==>${OFF} $*"; }
ok()   { echo "  ${GRN}ok${OFF}  $*"; }
warn() { echo "  ${YLW}!!${OFF}  $*"; }
die()  { echo "${RED}error:${OFF} $*" >&2; exit 1; }

DOMAIN="whatsapp.bomalogic.com"

# ---------------------------------------------------------------
say "Checking prerequisites"
# ---------------------------------------------------------------
command -v docker >/dev/null || die "docker not found"
docker compose version >/dev/null 2>&1 || die "docker compose v2 not found"
ok "docker present"

docker network inspect dokploy-network >/dev/null 2>&1 \
  || die "dokploy-network not found — is Dokploy running?"
ok "dokploy-network present"

# Traefik owns 80/443. If something else grabbed them, stop now.
if ! docker ps --format '{{.Names}}' | grep -q '^dokploy-traefik$'; then
  die "dokploy-traefik is not running — refusing to deploy without a reverse proxy"
fi
ok "traefik running"

AVAIL_MB=$(awk '/MemAvailable/ {print int($2/1024)}' /proc/meminfo)
if [ "$AVAIL_MB" -lt 1200 ]; then
  warn "only ${AVAIL_MB}MB available; MySQL may be OOM-killed"
  read -rp "  continue anyway? [y/N] " a; [[ "$a" =~ ^[Yy]$ ]] || exit 1
else
  ok "${AVAIL_MB}MB memory available"
fi

# ---------------------------------------------------------------
say "Configuring environment"
# ---------------------------------------------------------------
if [ -f .env ]; then
  ok ".env already exists — leaving it untouched"
else
  [ -f .env.example ] || die ".env.example missing"

  # Generated locally; never printed to the terminal or to logs.
  APP_KEY="base64:$(openssl rand -base64 32)"
  DB_PASSWORD="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-28)"
  DB_ROOT_PASSWORD="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-28)"

  cp .env.example .env
  set_env() {
    local k="$1" v="$2"
    if grep -qE "^${k}=" .env; then
      # '|' delimiter: base64 values contain '/'
      sed -i "s|^${k}=.*|${k}=${v}|" .env
    else
      echo "${k}=${v}" >> .env
    fi
  }

  set_env APP_KEY          "$APP_KEY"
  set_env DB_PASSWORD      "$DB_PASSWORD"
  set_env DB_ROOT_PASSWORD "$DB_ROOT_PASSWORD"
  set_env APP_URL          "https://${DOMAIN}"
  set_env APP_ENV          production
  set_env APP_DEBUG        false
  set_env FORCE_HTTPS      true

  chmod 600 .env
  ok "generated .env with fresh secrets (mode 600)"
  warn "integration keys are blank — see the note at the end"
fi

# Refuse to deploy a config that would leak or misbehave.
grep -qE '^APP_KEY=base64:.+' .env || die "APP_KEY missing or malformed in .env"
grep -qE '^APP_DEBUG=false'    .env || die "APP_DEBUG must be false in production"
grep -qE '^DB_PASSWORD=.+'     .env || die "DB_PASSWORD is empty"
grep -qE '^DB_ROOT_PASSWORD=.+' .env || die "DB_ROOT_PASSWORD is empty"
ok "configuration validated"

# ---------------------------------------------------------------
say "Building and starting (first run takes 5-10 minutes)"
# ---------------------------------------------------------------
docker compose up -d --build

# ---------------------------------------------------------------
say "Waiting for the application to become healthy"
# ---------------------------------------------------------------
for i in $(seq 1 60); do
  if docker compose exec -T app curl -fsS http://127.0.0.1/up >/dev/null 2>&1; then
    ok "application responding"
    break
  fi
  [ "$i" = 60 ] && {
    warn "not healthy after 5 minutes; recent logs:"
    docker compose logs --tail=40 app
    die "startup failed"
  }
  sleep 5
done

# ---------------------------------------------------------------
say "Verifying public access"
# ---------------------------------------------------------------
for i in $(seq 1 24); do
  CODE=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "https://${DOMAIN}/up" || echo 000)
  case "$CODE" in
    200) ok "https://${DOMAIN} is live"; break ;;
    000|404|502|503)
      # Let's Encrypt issuance and Traefik discovery both take a moment.
      [ "$i" = 24 ] && warn "still returning ${CODE} after 2 minutes — see troubleshooting below"
      sleep 5 ;;
    *) warn "unexpected status ${CODE}"; break ;;
  esac
done

echo
say "Status"
docker compose ps
echo
cat <<EOF
${BOLD}Next steps${OFF}

  1. Add your integration credentials — Pusher, OpenAI, SMTP, and your
     payment gateway. Copy them from the .env on your laptop:

         nano .env
         docker compose up -d

     Until then: no live chat, no AI replies, no outgoing email.

  2. Back up the generated secrets. They exist only in ./.env :

         grep -E '^(APP_KEY|DB_PASSWORD|DB_ROOT_PASSWORD)=' .env

     Put them in a password manager. Losing APP_KEY makes all encrypted
     data unrecoverable.

  3. Pin dependency versions for reproducible rebuilds:

         docker compose cp app:/var/www/html/composer.lock ./composer.lock
         git add composer.lock && git commit -m 'Add composer.lock' && git push

${BOLD}If the site is not reachable${OFF}

     docker compose logs --tail=50 app
     docker compose logs --tail=50 queue
     dig +short ${DOMAIN}          # expect 84.247.187.164
     docker logs dokploy-traefik --tail=30 2>&1 | grep -i acme
EOF
